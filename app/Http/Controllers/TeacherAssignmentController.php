<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\ClassTeacherAssignment;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherAssignmentController extends Controller
{
    public function index(): View
    {
        return view('teachers.assignments', [
            'teachers' => TeacherProfile::with('user', 'campus')->orderBy('employee_code')->get(),
            'sections' => ClassSection::with('campus')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'subjectAssignments' => TeacherSubjectAssignment::with([
                'teacherProfile.user', 'subject', 'classSection',
            ])->latest()->paginate(15, ['*'], 'subjects_page'),
            'classAssignments' => ClassTeacherAssignment::with([
                'teacherProfile.user', 'classSection',
            ])->latest()->paginate(15, ['*'], 'classes_page'),
        ]);
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_profile_id' => ['required', 'integer', Rule::exists('teacher_profiles', 'id')],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'class_section_id' => ['required', 'integer', Rule::exists('class_sections', 'id')],
        ]);

        [$teacher, $section] = $this->checkScope($data);
        if (Subject::whereKey($data['subject_id'])->value('school_id') !== $teacher->school_id) {
            throw ValidationException::withMessages(['subject_id' => 'Subject and teacher must belong to the same school.']);
        }
        if (TeacherSubjectAssignment::where($data)->exists()) {
            throw ValidationException::withMessages(['subject_id' => 'This teaching assignment already exists.']);
        }

        TeacherSubjectAssignment::create($data);
        return to_route('teacher-assignments.index')->with('success', 'Subject assigned.');
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_profile_id' => ['required', 'integer', Rule::exists('teacher_profiles', 'id')],
            'class_section_id' => [
                'required', 'integer', Rule::exists('class_sections', 'id'),
                Rule::unique('class_teacher_assignments', 'class_section_id'),
            ],
        ]);

        $this->checkScope($data);
        ClassTeacherAssignment::create($data);
        return to_route('teacher-assignments.index')->with('success', 'Class teacher assigned.');
    }

    private function checkScope(array $data): array
    {
        $teacher = TeacherProfile::findOrFail($data['teacher_profile_id']);
        $section = ClassSection::with('campus')->findOrFail($data['class_section_id']);

        if ($teacher->campus_id !== $section->campus_id || $teacher->school_id !== $section->campus->school_id) {
            throw ValidationException::withMessages([
                'class_section_id' => 'The class and teacher must belong to the same campus.',
            ]);
        }

        return [$teacher, $section];
    }

    public function destroySubject(TeacherSubjectAssignment $assignment): RedirectResponse
    {
        $assignment->delete();
        return to_route('teacher-assignments.index')->with('success', 'Subject assignment removed.');
    }

    public function destroyClass(ClassTeacherAssignment $assignment): RedirectResponse
    {
        $assignment->delete();
        return to_route('teacher-assignments.index')->with('success', 'Class teacher assignment removed.');
    }
}
