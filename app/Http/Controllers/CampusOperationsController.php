<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\ExamResult;
use App\Models\Examination;
use App\Models\FeeCharge;
use App\Models\FeePayment;
use App\Models\LibraryLoan;
use App\Models\School;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use App\Models\TeacherProfile;
use App\Models\TimetableEntry;
use Illuminate\Contracts\View\View;

class CampusOperationsController extends Controller
{
    /**
     * Aggregates only: no legacy school-wide module routes are exposed here.
     * Student-linked records require matching ownership on BOTH the student
     * and its class section. Unassigned and inconsistent data is excluded.
     */
    public function index(School $school, Campus $campus): View
    {
        $campusId = $campus->id;
        $schoolId = $school->id;

        $enrollmentScope = static fn ($query) => $query
            ->whereHas('student', static fn ($student) => $student
                ->where('school_id', $schoolId)->where('campus_id', $campusId))
            ->whereHas('classSection', static fn ($section) => $section
                ->where('campus_id', $campusId)
                ->whereHas('campus', static fn ($owner) => $owner->where('school_id', $schoolId)));

        $numbers = [
            'teachers' => TeacherProfile::query()
                ->where('school_id', $schoolId)->where('campus_id', $campusId)->count(),
            'classes' => ClassSection::query()
                ->where('campus_id', $campusId)
                ->whereHas('campus', fn ($owner) => $owner->where('school_id', $schoolId))->count(),
            'enrollments' => ClassEnrollment::query()->where($enrollmentScope)->count(),
            'student_attendance' => StudentAttendance::query()
                ->whereHas('enrollment', $enrollmentScope)->count(),
            'staff_attendance' => StaffAttendance::query()
                ->whereHas('teacherProfile', fn ($teacher) => $teacher
                    ->where('school_id', $schoolId)->where('campus_id', $campusId))->count(),
            'exams' => Examination::query()->whereHas('classSection', fn ($section) => $section
                ->where('campus_id', $campusId)
                ->whereHas('campus', fn ($owner) => $owner->where('school_id', $schoolId)))->count(),
            'results' => ExamResult::query()
                ->whereHas('enrollment', $enrollmentScope)
                ->whereHas('examination.classSection', fn ($section) => $section
                    ->where('campus_id', $campusId)
                    ->whereHas('campus', fn ($owner) => $owner->where('school_id', $schoolId)))->count(),
            'fee_charges' => FeeCharge::query()->whereHas('enrollment', $enrollmentScope)->count(),
            'fee_payments' => FeePayment::query()
                ->whereHas('charge.enrollment', $enrollmentScope)->count(),
            'library_loans' => LibraryLoan::query()
                ->whereHas('enrollment', $enrollmentScope)->count(),
            'timetable_entries' => TimetableEntry::query()
                ->whereHas('classSection', fn ($section) => $section
                    ->where('campus_id', $campusId)
                    ->whereHas('campus', fn ($owner) => $owner->where('school_id', $schoolId)))
                ->whereHas('teacherProfile', fn ($teacher) => $teacher
                    ->where('school_id', $schoolId)->where('campus_id', $campusId))->count(),
        ];

        return view('school-campuses.operations', compact('school', 'campus', 'numbers'));
    }
}
