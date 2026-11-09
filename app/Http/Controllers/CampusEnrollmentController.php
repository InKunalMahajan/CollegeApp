<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampusEnrollmentController extends Controller
{
    public function index(School $school, Campus $campus): View
    {
        $sections = ClassSection::query()
            ->where('campus_id', $campus->id)
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $school->id))
            ->orderBy('name')->get();

        $students = Student::query()
            ->where('school_id', $school->id)->where('campus_id', $campus->id)
            ->where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get(['id','first_name','last_name','admission_number']);

        $enrollments = ClassEnrollment::with(['student','classSection'])
            ->whereHas('student', fn ($q) => $q->where('school_id', $school->id)->where('campus_id', $campus->id))
            ->whereHas('classSection', fn ($q) => $q->where('campus_id', $campus->id)
                ->whereHas('academicYear', fn ($year) => $year->where('school_id', $school->id)))
            ->latest()->paginate(20);

        return view('school-campuses.enrollments', compact('school', 'campus', 'sections', 'students', 'enrollments'));
    }

    public function store(Request $request, School $school, Campus $campus, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'class_section_id' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($request, $school, $campus, $data, $audit) {
            // Resolve the student and class from server-side campus scope, not
            // from submitted school_id/campus_id fields.
            $student = Student::query()
                ->whereKey($data['student_id'])
                ->where('school_id', $school->id)
                ->where('campus_id', $campus->id)
                ->where('status', 'active')
                ->lockForUpdate()->firstOrFail();

            $section = ClassSection::query()
                ->whereKey($data['class_section_id'])
                ->where('campus_id', $campus->id)
                ->whereHas('academicYear', fn ($year) => $year->where('school_id', $school->id))
                ->lockForUpdate()->firstOrFail();

            $enrollment = ClassEnrollment::query()->firstOrCreate([
                'student_id' => $student->id,
                'class_section_id' => $section->id,
            ]);

            if ($enrollment->wasRecentlyCreated) {
                $audit->record($school->id, $request->user()->id, 'campus.academics.student.enrolled',
                    'class_enrollment', $enrollment->id, ['campus_id' => $campus->id]);
            }
        });

        return to_route('campus.enrollments.index', [$school, $campus])
            ->with('success', 'Enrollment saved.');
    }
}
