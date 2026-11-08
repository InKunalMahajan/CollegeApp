<?php

namespace App\Http\Controllers;

use App\Models\CurriculumLesson;
use App\Models\CurriculumUnit;
use App\Models\LessonProgress;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CurriculumController extends Controller
{
    public function index(): View
    {
        return view('curriculum.index', [
            'subjects' => Subject::with('school')->orderBy('name')->get(),
            'units' => CurriculumUnit::with(['subject', 'lessons'])->orderBy('subject_id')->orderBy('sequence')->get(),
        ]);
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'title' => ['required', 'string', 'max:200'],
            'sequence' => ['required', 'integer', 'between:1,9999',
                Rule::unique('curriculum_units', 'sequence')->where('subject_id', $request->input('subject_id'))],
            'learning_outcome' => ['nullable', 'string', 'max:4000'],
        ]);
        CurriculumUnit::create($data);
        return to_route('curriculum.index')->with('success', 'Curriculum unit created.');
    }

    public function storeLesson(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'curriculum_unit_id' => ['required', 'integer', Rule::exists('curriculum_units', 'id')],
            'title' => ['required', 'string', 'max:200'],
            'sequence' => ['required', 'integer', 'between:1,9999',
                Rule::unique('curriculum_lessons', 'sequence')->where('curriculum_unit_id', $request->input('curriculum_unit_id'))],
            'estimated_minutes' => ['required', 'integer', 'between:1,10000'],
            'objective' => ['nullable', 'string', 'max:4000'],
        ]);
        CurriculumLesson::create($data);
        return to_route('curriculum.index')->with('success', 'Lesson created.');
    }

    public function myProgress(): View
    {
        $teacher = auth()->user();
        $assignments = TeacherSubjectAssignment::with(['subject', 'classSection', 'teacherProfile'])
            ->whereHas('teacherProfile', fn ($query) => $query->where('user_id', $teacher->id))->get();
        $subjectIds = $assignments->pluck('subject_id')->unique();
        $units = CurriculumUnit::with('lessons')
            ->whereIn('subject_id', $subjectIds)->orderBy('sequence')->get();
        $progress = LessonProgress::whereIn('teacher_subject_assignment_id', $assignments->pluck('id'))
            ->get()->keyBy(fn ($row) => $row->teacher_subject_assignment_id.':'.$row->curriculum_lesson_id);
        return view('curriculum.mine', compact('assignments', 'units', 'progress'));
    }

    public function storeProgress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_subject_assignment_id' => ['required', 'integer', Rule::exists('teacher_subject_assignments', 'id')],
            'curriculum_lesson_id' => ['required', 'integer', Rule::exists('curriculum_lessons', 'id')],
            'status' => ['required', Rule::in(['not_started', 'in_progress', 'completed'])],
            'completed_on' => ['nullable', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $assignment = TeacherSubjectAssignment::with('teacherProfile')->findOrFail($data['teacher_subject_assignment_id']);
        $lesson = CurriculumLesson::with('unit')->findOrFail($data['curriculum_lesson_id']);
        if ($assignment->teacherProfile->user_id !== auth()->id()) abort(403);
        if ($assignment->subject_id !== $lesson->unit->subject_id) {
            throw ValidationException::withMessages(['curriculum_lesson_id' => 'Lesson is not part of your assigned subject.']);
        }
        if ($data['status'] === 'completed' && empty($data['completed_on'])) {
            throw ValidationException::withMessages(['completed_on' => 'Completion date is required for a completed lesson.']);
        }
        LessonProgress::updateOrCreate(
            ['teacher_subject_assignment_id' => $assignment->id, 'curriculum_lesson_id' => $lesson->id],
            ['status' => $data['status'],
             'completed_on' => $data['status'] === 'completed' ? $data['completed_on'] : null,
             'remarks' => $data['remarks'] ?? null]
        );
        return to_route('curriculum.mine')->with('success', 'Teaching progress saved.');
    }
}
