<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampusTeachingController extends Controller
{
    public function index(School $school, Campus $campus): View
    {
        $teachers = TeacherProfile::with('user')->where('school_id', $school->id)
            ->where('campus_id', $campus->id)->orderBy('employee_code')->get();
        $sections = ClassSection::where('campus_id', $campus->id)
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $school->id))
            ->orderBy('name')->get();
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
        $assignments = TeacherSubjectAssignment::with(['teacherProfile.user', 'subject', 'classSection'])
            ->whereHas('teacherProfile', fn ($q) => $q->where('school_id', $school->id)
                ->where('campus_id', $campus->id))
            ->whereHas('classSection', fn ($q) => $q->where('campus_id', $campus->id)
                ->whereHas('academicYear', fn ($y) => $y->where('school_id', $school->id)))
            ->whereHas('subject', fn ($q) => $q->where('school_id', $school->id))
            ->latest()->paginate(20);
        return view('school-campuses.teaching', compact('school', 'campus', 'teachers', 'sections', 'subjects', 'assignments'));
    }

    public function store(Request $request, School $school, Campus $campus, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'teacher_profile_id' => ['required', 'integer'],
            'class_section_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($request, $school, $campus, $data, $audit) {
            $teacher = TeacherProfile::whereKey($data['teacher_profile_id'])
                ->where('school_id', $school->id)->where('campus_id', $campus->id)
                ->lockForUpdate()->firstOrFail();
            $section = ClassSection::whereKey($data['class_section_id'])
                ->where('campus_id', $campus->id)
                ->whereHas('academicYear', fn ($y) => $y->where('school_id', $school->id))
                ->lockForUpdate()->firstOrFail();
            $subject = Subject::whereKey($data['subject_id'])
                ->where('school_id', $school->id)->firstOrFail();

            $assignment = TeacherSubjectAssignment::firstOrCreate([
                'teacher_profile_id' => $teacher->id,
                'class_section_id' => $section->id,
                'subject_id' => $subject->id,
            ]);
            if ($assignment->wasRecentlyCreated) {
                $audit->record($school->id, $request->user()->id,
                    'campus.teaching.assigned', 'teacher_subject_assignment', $assignment->id,
                    ['campus_id' => $campus->id]);
            }
        });

        return to_route('campus.teaching.index', [$school, $campus])
            ->with('success', 'Teaching assignment saved.');
    }
}
