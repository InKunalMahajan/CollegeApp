<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use App\Models\TeacherProfile;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampusAttendanceController extends Controller
{
    private function verifiedEnrollments(School $school, Campus $campus)
    {
        return ClassEnrollment::query()
            ->whereHas('student', fn ($q) => $q->where('school_id', $school->id)
                ->where('campus_id', $campus->id))
            ->whereHas('classSection', fn ($q) => $q->where('campus_id', $campus->id)
                ->whereHas('academicYear', fn ($y) => $y->where('school_id', $school->id)));
    }

    public function index(School $school, Campus $campus): View
    {
        $enrollments = $this->verifiedEnrollments($school, $campus)
            ->with(['student','classSection'])->orderBy('id')->limit(200)->get();
        $teachers = TeacherProfile::with('user')->where('school_id', $school->id)
            ->where('campus_id', $campus->id)->orderBy('employee_code')->get();
        $studentRecords = StudentAttendance::with('enrollment.student')
            ->whereHas('enrollment', fn ($q) => $q
                ->whereIn('id', $this->verifiedEnrollments($school, $campus)->select('id')))
            ->latest()->paginate(20, ['*'], 'students_page');
        $staffRecords = StaffAttendance::with('teacherProfile.user')
            ->whereHas('teacherProfile', fn ($q) => $q->where('school_id', $school->id)
                ->where('campus_id', $campus->id))->latest()
            ->paginate(20, ['*'], 'staff_page');

        return view('school-campuses.attendance', compact('school','campus','enrollments','teachers','studentRecords','staffRecords'));
    }

    public function student(Request $request, School $school, Campus $campus, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'class_enrollment_id'=>['required','integer'],
            'attendance_date'=>['required','date_format:Y-m-d','before_or_equal:today'],
            'status'=>['required',Rule::in(['present','absent','late','excused'])],
        ]);
        DB::transaction(function () use ($request, $school, $campus, $data, $audit) {
            $enrollment = $this->verifiedEnrollments($school, $campus)
                ->whereKey($data['class_enrollment_id'])->lockForUpdate()->firstOrFail();
            $record = StudentAttendance::updateOrCreate([
                'class_enrollment_id'=>$enrollment->id,
                'attendance_date'=>$data['attendance_date'],
            ],['status'=>$data['status']]);
            $audit->record($school->id, $request->user()->id, 'campus.attendance.student.saved',
                'student_attendance', $record->id, ['campus_id'=>$campus->id]);
        });
        return to_route('campus.attendance.index', [$school,$campus])->with('success','Student attendance saved.');
    }

    public function staff(Request $request, School $school, Campus $campus, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'teacher_profile_id'=>['required','integer'],
            'attendance_date'=>['required','date_format:Y-m-d','before_or_equal:today'],
            'status'=>['required',Rule::in(['present','absent','late','excused'])],
        ]);
        DB::transaction(function () use ($request,$school,$campus,$data,$audit) {
            $teacher = TeacherProfile::whereKey($data['teacher_profile_id'])
                ->where('school_id',$school->id)->where('campus_id',$campus->id)
                ->lockForUpdate()->firstOrFail();
            $record = StaffAttendance::updateOrCreate([
                'teacher_profile_id'=>$teacher->id,
                'attendance_date'=>$data['attendance_date'],
            ],['status'=>$data['status']]);
            $audit->record($school->id,$request->user()->id,'campus.attendance.staff.saved',
                'staff_attendance',$record->id,['campus_id'=>$campus->id]);
        });
        return to_route('campus.attendance.index',[$school,$campus])->with('success','Staff attendance saved.');
    }
}
