<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TeacherProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(): View
    {
        return view('attendance.index', [
            'sections' => ClassSection::with('campus')->orderBy('name')->get(),
            'students' => Student::orderBy('first_name')->get(),
            'teachers' => TeacherProfile::with('user')->get(),
            'enrollments' => ClassEnrollment::with('student', 'classSection')->latest()->limit(100)->get(),
            'studentRows' => StudentAttendance::with('enrollment.student', 'enrollment.classSection')->latest()->limit(50)->get(),
            'staffRows' => StaffAttendance::with('teacherProfile.user')->latest()->limit(50)->get(),
        ]);
    }

    public function enroll(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'class_section_id' => ['required', 'integer', Rule::exists('class_sections', 'id')],
        ]);
        ClassEnrollment::firstOrCreate($data);
        return to_route('attendance.index')->with('success', 'Student enrolled in class section.');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_enrollment_id' => ['required', 'integer', Rule::exists('class_enrollments', 'id')],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
        ]);
        StudentAttendance::updateOrCreate(
            ['class_enrollment_id' => $data['class_enrollment_id'], 'attendance_date' => $data['attendance_date']],
            ['status' => $data['status']]
        );
        return to_route('attendance.index')->with('success', 'Student attendance saved.');
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_profile_id' => ['required', 'integer', Rule::exists('teacher_profiles', 'id')],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
        ]);
        StaffAttendance::updateOrCreate(
            ['teacher_profile_id' => $data['teacher_profile_id'], 'attendance_date' => $data['attendance_date']],
            ['status' => $data['status']]
        );
        return to_route('attendance.index')->with('success', 'Staff attendance saved.');
    }
}
