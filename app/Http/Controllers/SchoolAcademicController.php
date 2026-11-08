<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\School;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\TimetableEntry;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolAcademicController extends Controller
{
    // All record resolvers check the route-bound school, never trust
    // arbitrary resource IDs supplied by clients.
    private function section(School $school, int $id): ClassSection
    {
        return ClassSection::whereKey($id)
            ->whereHas('campus', fn ($q) => $q->where('school_id', $school->id))
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $school->id))
            ->firstOrFail();
    }

    private function enrollment(School $school, int $id): ClassEnrollment
    {
        return ClassEnrollment::whereKey($id)
            ->whereHas('classSection.campus', fn ($q) => $q->where('school_id', $school->id))
            ->firstOrFail();
    }

    private function teacher(School $school, int $id): TeacherProfile
    {
        return TeacherProfile::whereKey($id)
            ->where('school_id', $school->id)
            ->whereHas('campus', fn ($q) => $q->where('school_id', $school->id))
            ->firstOrFail();
    }

    private function subject(School $school, int $id): Subject
    {
        return Subject::whereKey($id)->where('school_id', $school->id)->firstOrFail();
    }

    private function exam(School $school, int $id): Examination
    {
        return Examination::whereKey($id)
            ->whereHas('classSection.campus', fn ($q) => $q->where('school_id', $school->id))
            ->whereHas('subject', fn ($q) => $q->where('school_id', $school->id))
            ->firstOrFail();
    }

    private function teaching(School $school, int $id): TeacherSubjectAssignment
    {
        return TeacherSubjectAssignment::whereKey($id)
            ->whereHas('teacherProfile', fn ($q) => $q->where('school_id', $school->id))
            ->whereHas('subject', fn ($q) => $q->where('school_id', $school->id))
            ->whereHas('classSection.campus', fn ($q) => $q->where('school_id', $school->id))
            ->firstOrFail();
    }

    public function index(School $school): View
    {
        $sections = ClassSection::with('campus')
            ->whereHas('campus', fn ($q) => $q->where('school_id', $school->id))
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $school->id))
            ->orderBy('name')->get();
        $sectionIds = $sections->pluck('id');

        $enrollments = ClassEnrollment::with('student', 'classSection')
            ->whereIn('class_section_id', $sectionIds)->latest()->limit(100)->get();
        $teachers = TeacherProfile::with('user')
            ->where('school_id', $school->id)->orderBy('employee_code')->get();
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
        $teachings = TeacherSubjectAssignment::with('teacherProfile.user', 'subject', 'classSection')
            ->whereIn('class_section_id', $sectionIds)
            ->whereHas('teacherProfile', fn ($q) => $q->where('school_id', $school->id))
            ->whereHas('subject', fn ($q) => $q->where('school_id', $school->id))
            ->get();
        $exams = Examination::with('classSection', 'subject')
            ->whereIn('class_section_id', $sectionIds)
            ->whereHas('subject', fn ($q) => $q->where('school_id', $school->id))
            ->latest()->limit(100)->get();
        $timetable = TimetableEntry::with('classSection', 'subject', 'teacherProfile.user')
            ->whereIn('class_section_id', $sectionIds)
            ->whereHas('teacherProfile', fn ($q) => $q->where('school_id', $school->id))
            ->whereHas('subject', fn ($q) => $q->where('school_id', $school->id))
            ->orderBy('day_of_week')->orderBy('starts_at')->limit(100)->get();
        $attendance = StudentAttendance::with('enrollment.student')
            ->whereHas('enrollment', fn ($q) => $q->whereIn('class_section_id', $sectionIds))
            ->latest()->limit(30)->get();

        return view('school-academics.index', compact(
            'school', 'sections', 'enrollments', 'teachers', 'subjects',
            'teachings', 'exams', 'timetable', 'attendance'
        ));
    }

    public function enroll(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required','integer', Rule::exists('students','id')],
            'class_section_id' => ['required','integer', Rule::exists('class_sections','id')],
        ]);
        $section = $this->section($school, (int)$data['class_section_id']);
        $student = Student::findOrFail($data['student_id']);
        // School ownership is canonical; unassigned legacy records fail closed.
        abort_unless($student->school_id !== null && (int) $student->school_id === (int) $school->id, 404);

        DB::transaction(function () use ($student,$section,$school,$audit) {
            $created = ClassEnrollment::firstOrCreate([
                'student_id' => $student->id,
                'class_section_id' => $section->id,
            ]);
            if ($created->wasRecentlyCreated) {
                $audit->record($school->id, auth()->id(), 'academics.student.enrolled', 'class_enrollment', $created->id);
            }
        });
        return to_route('school-academics.index',$school)->with('success','Enrollment saved.');
    }

    public function assignTeacher(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'teacher_profile_id' => ['required','integer',Rule::exists('teacher_profiles','id')],
            'subject_id' => ['required','integer',Rule::exists('subjects','id')],
            'class_section_id' => ['required','integer',Rule::exists('class_sections','id')],
        ]);
        $teacher = $this->teacher($school, (int)$data['teacher_profile_id']);
        $subject = $this->subject($school, (int)$data['subject_id']);
        $section = $this->section($school, (int)$data['class_section_id']);
        abort_unless($teacher->campus_id === $section->campus_id, 404);

        DB::transaction(function () use ($teacher,$subject,$section,$school,$audit) {
            $assignment = TeacherSubjectAssignment::firstOrCreate([
                'teacher_profile_id'=>$teacher->id,'subject_id'=>$subject->id,'class_section_id'=>$section->id,
            ]);
            if ($assignment->wasRecentlyCreated) {
                $audit->record($school->id, auth()->id(),'academics.teacher.assigned','teacher_subject_assignment',$assignment->id);
            }
        });
        return to_route('school-academics.index',$school)->with('success','Teaching assignment saved.');
    }

    public function recordAttendance(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'class_enrollment_id'=>['required','integer',Rule::exists('class_enrollments','id')],
            'attendance_date'=>['required','date','before_or_equal:today'],
            'status'=>['required',Rule::in(['present','absent','late','excused'])],
        ]);
        $enrollment = $this->enrollment($school, (int)$data['class_enrollment_id']);

        DB::transaction(function () use ($data,$enrollment,$school,$audit) {
            $row = StudentAttendance::where('class_enrollment_id',$enrollment->id)
                ->whereDate('attendance_date',$data['attendance_date'])->lockForUpdate()->first();
            if ($row) {
                $row->update(['status'=>$data['status']]);
            } else {
                $row=StudentAttendance::create($data);
            }
            $audit->record($school->id,auth()->id(),'academics.attendance.saved','student_attendance',$row->id,[
                'status'=>$data['status'],
            ]);
        });
        return to_route('school-academics.index',$school)->with('success','Attendance saved.');
    }

    public function storeTimetable(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'teacher_subject_assignment_id'=>['required','integer',Rule::exists('teacher_subject_assignments','id')],
            'day_of_week'=>['required','integer','between:1,7'],
            'starts_at'=>['required','date_format:H:i'],
            'ends_at'=>['required','date_format:H:i','after:starts_at'],
            'room'=>['nullable','string','max:80'],
        ]);
        $assignment = $this->teaching($school,(int)$data['teacher_subject_assignment_id']);
        DB::transaction(function () use ($data,$assignment,$school,$audit) {
            $conflict=TimetableEntry::where('day_of_week',$data['day_of_week'])
                ->where('starts_at','<',$data['ends_at'])->where('ends_at','>',$data['starts_at'])
                ->where(fn ($q) => $q->where('class_section_id',$assignment->class_section_id)
                    ->orWhere('teacher_profile_id',$assignment->teacher_profile_id))
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages(['starts_at'=>'Class or teacher has an overlapping period.']);
            }
            $entry=TimetableEntry::create([
                'teacher_profile_id'=>$assignment->teacher_profile_id,
                'class_section_id'=>$assignment->class_section_id,
                'subject_id'=>$assignment->subject_id,
                'day_of_week'=>$data['day_of_week'],
                'starts_at'=>$data['starts_at'],
                'ends_at'=>$data['ends_at'],
                'room'=>$data['room'] ?? null,
            ]);
            $audit->record($school->id,auth()->id(),'academics.timetable.created','timetable_entry',$entry->id);
        });
        return to_route('school-academics.index',$school)->with('success','Timetable period created.');
    }

    public function storeExam(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data=$request->validate([
            'class_section_id'=>['required','integer',Rule::exists('class_sections','id')],
            'subject_id'=>['required','integer',Rule::exists('subjects','id')],
            'name'=>['required','string','max:150'],
            'exam_date'=>['required','date'],
            'max_marks'=>['required','integer','between:1,10000'],
            'pass_marks'=>['required','integer','min:0','lte:max_marks'],
        ]);
        $section=$this->section($school,(int)$data['class_section_id']);
        $subject=$this->subject($school,(int)$data['subject_id']);

        DB::transaction(function () use ($data,$section,$subject,$school,$audit) {
            $exists=Examination::where('class_section_id',$section->id)
                ->where('subject_id',$subject->id)->where('name',$data['name'])->exists();
            if ($exists) {
                throw ValidationException::withMessages(['name'=>'This examination already exists for this class and subject.']);
            }
            $exam=Examination::create($data);
            $audit->record($school->id,auth()->id(),'academics.exam.created','examination',$exam->id);
        });
        return to_route('school-academics.index',$school)->with('success','Examination created.');
    }

    public function recordResult(Request $request, School $school, Examination $exam, SchoolAuditTrail $audit): RedirectResponse
    {
        $exam=$this->exam($school,$exam->id);
        $data=$request->validate([
            'class_enrollment_id'=>['required','integer',Rule::exists('class_enrollments','id')],
            'status'=>['required',Rule::in(['graded','absent','exempt'])],
            'marks_obtained'=>['nullable','required_if:status,graded','integer','min:0','max:'.$exam->max_marks],
            'remarks'=>['nullable','string','max:2000'],
        ]);
        $enrollment=$this->enrollment($school,(int)$data['class_enrollment_id']);
        abort_unless($enrollment->class_section_id === $exam->class_section_id, 404);
        if ($data['status'] !== 'graded' && isset($data['marks_obtained'])) {
            throw ValidationException::withMessages(['marks_obtained'=>'Only graded results may have marks.']);
        }

        DB::transaction(function () use ($data,$enrollment,$exam,$school,$audit) {
            $result=ExamResult::updateOrCreate(
                ['examination_id'=>$exam->id,'class_enrollment_id'=>$enrollment->id],
                ['status'=>$data['status'],
                 'marks_obtained'=>$data['status']==='graded' ? $data['marks_obtained'] : null,
                 'remarks'=>$data['remarks'] ?? null]
            );
            $audit->record($school->id,auth()->id(),'academics.result.saved','exam_result',$result->id,[
                'status'=>$data['status'],
            ]);
        });
        return to_route('school-academics.index',$school)->with('success','Examination result saved.');
    }
}
