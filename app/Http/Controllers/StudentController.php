<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $students = Student::with('course')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%");
            }))->latest()->paginate(10)->withQueryString();
        return view('students.index', compact('students', 'search'));
    }

    public function create(): View
    {
        return view('students.form', ['student' => new Student(), 'courses' => Course::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Student::create($this->validated($request));
        return to_route('students.index')->with('success', 'Student added.');
    }

    public function edit(Student $student): View
    {
        return view('students.form', ['student' => $student, 'courses' => Course::orderBy('name')->get()]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $student->update($this->validated($request, $student));
        return to_route('students.index')->with('success', 'Student updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();
        return to_route('students.index')->with('success', 'Student deleted.');
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'admission_number' => ['required', 'string', 'max:40', Rule::unique('students')->ignore($student?->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('students')->ignore($student?->id)],
            'admission_date' => ['required', 'date'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'status' => ['required', Rule::in(['active','inactive','graduated'])],
        ]);
    }
}
