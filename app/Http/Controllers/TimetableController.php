<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\TimetableEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    public function index(): View
    {
        return view('timetables.index', [
            'entries' => TimetableEntry::with('teacherProfile.user', 'classSection.campus', 'subject')
                ->orderBy('day_of_week')->orderBy('starts_at')->get(),
            'sections' => ClassSection::with('campus')->orderBy('name')->get(),
            'teachers' => TeacherProfile::with('user')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_section_id' => ['required', 'integer', Rule::exists('class_sections', 'id')],
            'teacher_profile_id' => ['required', 'integer', Rule::exists('teacher_profiles', 'id')],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:80'],
        ]);

        $assigned = TeacherSubjectAssignment::where('teacher_profile_id', $data['teacher_profile_id'])
            ->where('class_section_id', $data['class_section_id'])
            ->where('subject_id', $data['subject_id'])
            ->exists();

        if (! $assigned) {
            throw ValidationException::withMessages([
                'teacher_profile_id' => 'Teacher must already be assigned to this subject and class.',
            ]);
        }

        $conflict = TimetableEntry::where('day_of_week', $data['day_of_week'])
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->where(function ($query) use ($data) {
                $query->where('teacher_profile_id', $data['teacher_profile_id'])
                    ->orWhere('class_section_id', $data['class_section_id']);
            })->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'This overlaps an existing teacher or class period.',
            ]);
        }

        TimetableEntry::create($data);
        return to_route('timetables.index')->with('success', 'Timetable period added.');
    }

    public function destroy(TimetableEntry $entry): RedirectResponse
    {
        $entry->delete();
        return to_route('timetables.index')->with('success', 'Timetable period removed.');
    }

    public function mine(): View
    {
        $profile = TeacherProfile::where('user_id', auth()->id())->first();

        return view('timetables.mine', [
            'entries' => $profile
                ? TimetableEntry::with('subject', 'classSection')
                    ->where('teacher_profile_id', $profile->id)
                    ->orderBy('day_of_week')->orderBy('starts_at')->get()
                : collect(),
        ]);
    }
}
