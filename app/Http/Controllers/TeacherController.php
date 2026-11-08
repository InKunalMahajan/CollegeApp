<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\School;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherController extends Controller
{
    public function index(): View
    {
        return view('teachers.index', [
            'teachers' => TeacherProfile::with(['user', 'school', 'campus'])->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function edit(TeacherProfile $teacher): View
    {
        return $this->form($teacher);
    }

    private function form(?TeacherProfile $teacher): View
    {
        return view('teachers.form', [
            'teacher' => $teacher,
            'users' => User::where('role', 'teacher')
                ->when($teacher, fn ($query) => $query->orWhere('id', $teacher->user_id))
                ->orderBy('name')->get(),
            'schools' => School::orderBy('name')->get(),
            'campuses' => Campus::with('school')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TeacherProfile::create($this->validated($request));
        return to_route('teachers.index')->with('success', 'Teacher profile created.');
    }

    public function update(Request $request, TeacherProfile $teacher): RedirectResponse
    {
        $teacher->update($this->validated($request, $teacher));
        return to_route('teachers.index')->with('success', 'Teacher profile updated.');
    }

    private function validated(Request $request, ?TeacherProfile $teacher = null): array
    {
        $schoolId = $request->input('school_id');
        $data = $request->validate([
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('role', 'teacher'),
                Rule::unique('teacher_profiles', 'user_id')->ignore($teacher?->id),
            ],
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
            'campus_id' => ['required', 'integer', Rule::exists('campuses', 'id')->where('school_id', $schoolId)],
            'employee_code' => [
                'required', 'string', 'max:40',
                Rule::unique('teacher_profiles', 'employee_code')
                    ->where('school_id', $schoolId)->ignore($teacher?->id),
            ],
            'designation' => ['nullable', 'string', 'max:255'],
            'joined_on' => ['nullable', 'date'],
        ]);

        if ($teacher && $teacher->school_id !== (int) $data['school_id'] &&
            ($teacher->subjectAssignments()->exists() || $teacher->classTeacherAssignments()->exists())) {
            throw ValidationException::withMessages([
                'school_id' => 'Remove teaching assignments before changing the school.',
            ]);
        }

        if ($teacher && $teacher->campus_id !== (int) $data['campus_id'] &&
            ($teacher->subjectAssignments()->exists() || $teacher->classTeacherAssignments()->exists())) {
            throw ValidationException::withMessages([
                'campus_id' => 'Remove teaching assignments before changing the campus.',
            ]);
        }

        return $data;
    }
}
