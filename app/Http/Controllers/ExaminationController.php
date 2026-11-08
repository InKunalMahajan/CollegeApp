<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\ExamResult;
use App\Models\Examination;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExaminationController extends Controller
{
    public function index(): View
    {
        return view('exams.index', [
            'exams' => Examination::with(['classSection', 'subject'])->latest()->paginate(20),
            'sections' => ClassSection::with('campus')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_section_id' => ['required', 'integer', Rule::exists('class_sections', 'id')],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'name' => ['required', 'string', 'max:150',
                Rule::unique('examinations', 'name')
                    ->where('class_section_id', $request->input('class_section_id'))
                    ->where('subject_id', $request->input('subject_id'))],
            'exam_date' => ['required', 'date'],
            'max_marks' => ['required', 'integer', 'between:1,10000'],
            'pass_marks' => ['required', 'integer', 'min:0', 'lte:max_marks'],
        ]);

        $section = ClassSection::with('campus')->findOrFail($data['class_section_id']);
        $subject = Subject::findOrFail($data['subject_id']);
        if ($section->campus->school_id !== $subject->school_id) {
            throw ValidationException::withMessages(['subject_id' => 'Subject and class must belong to the same school.']);
        }

        Examination::create($data);
        return to_route('exams.index')->with('success', 'Examination created.');
    }

    public function results(Examination $exam): View
    {
        $exam->load('subject', 'classSection');
        return view('exams.results', [
            'exam' => $exam,
            'enrollments' => ClassEnrollment::with('student')
                ->where('class_section_id', $exam->class_section_id)->get(),
            'results' => ExamResult::with('enrollment.student')
                ->where('examination_id', $exam->id)->get()->keyBy('class_enrollment_id'),
        ]);
    }

    public function storeResult(Request $request, Examination $exam): RedirectResponse
    {
        $data = $request->validate([
            'class_enrollment_id' => ['required', 'integer', Rule::exists('class_enrollments', 'id')],
            'status' => ['required', Rule::in(['graded', 'absent', 'exempt'])],
            'marks_obtained' => ['nullable', 'required_if:status,graded', 'integer', 'min:0', 'max:'.$exam->max_marks],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $enrollment = ClassEnrollment::findOrFail($data['class_enrollment_id']);
        if ($enrollment->class_section_id !== $exam->class_section_id) {
            throw ValidationException::withMessages(['class_enrollment_id' => 'Student is not enrolled in the examination class.']);
        }
        if ($data['status'] !== 'graded' && isset($data['marks_obtained'])) {
            throw ValidationException::withMessages(['marks_obtained' => 'Only graded results may have marks.']);
        }
        ExamResult::updateOrCreate(
            ['examination_id' => $exam->id, 'class_enrollment_id' => $enrollment->id],
            ['status' => $data['status'], 'marks_obtained' => $data['status'] === 'graded' ? $data['marks_obtained'] : null,
             'remarks' => $data['remarks'] ?? null]
        );
        return to_route('exams.results', $exam)->with('success', 'Result saved.');
    }
}
