<?php

namespace App\Http\Controllers;

use App\Models\AssignmentSubmission;
use App\Models\ClassEnrollment;
use App\Models\SchoolAssignment;
use App\Models\StudentAccount;
use App\Models\TeacherSubjectAssignment;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolAssignmentController extends Controller
{
    private function teacherAssignments(): \Illuminate\Database\Eloquent\Builder
    {
        return TeacherSubjectAssignment::query()
            ->whereHas('teacherProfile', fn ($q) => $q->where('user_id', auth()->id())
                ->whereHas('campus', fn ($campus) => $campus
                    ->whereColumn('campuses.school_id', 'teacher_profiles.school_id')))
            ->whereHas('classSection.campus', fn ($q) => $q
                ->whereIn('school_id', TeacherProfile::where('user_id',auth()->id())->select('school_id')))
            ->whereHas('classSection.academicYear', fn ($q) => $q
                ->whereIn('school_id',TeacherProfile::where('user_id',auth()->id())->select('school_id')))
            ->whereHas('subject', fn ($q) => $q
                ->whereIn('school_id',TeacherProfile::where('user_id',auth()->id())->select('school_id')));
    }

    private function teachingAssignment(SchoolAssignment $assignment): void
    {
        if (! $this->teacherAssignments()->whereKey($assignment->teacher_subject_assignment_id)->exists()) abort(403);
    }

    public function teacherIndex(): View
    {
        return view('assignments.teacher', [
            'teaching' => $this->teacherAssignments()->with('subject','classSection')->get(),
            'assignments' => SchoolAssignment::with('teachingAssignment.subject','teachingAssignment.classSection')
                ->whereIn('teacher_subject_assignment_id', $this->teacherAssignments()->select('id'))
                ->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_subject_assignment_id' => ['required','integer',Rule::exists('teacher_subject_assignments','id')],
            'title' => ['required','string','max:200'],
            'instructions' => ['required','string','max:10000'],
            'due_at' => ['required','date','after:now'],
            'max_marks' => ['required','integer','between:1,10000'],
        ]);
        if (! $this->teacherAssignments()->whereKey($data['teacher_subject_assignment_id'])->exists()) abort(403);
        SchoolAssignment::create($data + ['status'=>'draft']);
        return to_route('assignments.teacher')->with('success','Draft assignment created.');
    }

    public function publish(SchoolAssignment $assignment): RedirectResponse
    {
        $this->teachingAssignment($assignment);
        if ($assignment->due_at->isPast()) {
            throw ValidationException::withMessages(['due_at'=>'Due date has passed; create a new assignment.']);
        }
        $assignment->update(['status'=>'published']);
        return to_route('assignments.teacher')->with('success','Assignment published.');
    }

    public function submissions(SchoolAssignment $assignment): View
    {
        $this->teachingAssignment($assignment);
        return view('assignments.submissions', [
            'assignment' => $assignment,
            'submissions' => $assignment->submissions()
                ->whereHas('enrollment', fn ($q) => $q
                    ->where('class_section_id',$assignment->teachingAssignment->class_section_id)
                    ->whereHas('student', fn ($s) => $s
                        ->where('school_id',$assignment->teachingAssignment->teacherProfile->school_id)))
                ->with('enrollment.student')->latest()->get(),
        ]);
    }

    public function grade(Request $request, AssignmentSubmission $submission): RedirectResponse
    {
        $assignment = $submission->assignment;
        $this->teachingAssignment($assignment);
        abort_unless($submission->enrollment->class_section_id === $assignment->teachingAssignment->class_section_id
            && (int)$submission->enrollment->student->school_id === (int)$assignment->teachingAssignment->teacherProfile->school_id, 404);
        $data = $request->validate([
            'marks' => ['required','integer','min:0','max:'.$assignment->max_marks],
            'feedback' => ['nullable','string','max:5000'],
        ]);
        $submission->update($data + ['graded_at'=>now()]);
        return to_route('assignments.submissions', $assignment)->with('success','Submission graded.');
    }

    private function linkedStudent(): ?StudentAccount
    {
        $link=StudentAccount::with('student')->where('user_id',auth()->id())->first();
        // An unowned or deleted student cannot use any scoped assignment.
        return $link && $link->student && $link->student->school_id !== null ? $link : null;
    }

    private function studentEnrollments(StudentAccount $link): \Illuminate\Database\Eloquent\Builder
    {
        return ClassEnrollment::query()
            ->where('student_id',$link->student_id)
            ->whereHas('classSection.campus',fn($q)=>$q->where('school_id',$link->student->school_id))
            ->whereHas('classSection.academicYear',fn($q)=>$q->where('school_id',$link->student->school_id));
    }

    private function accessibleAssignments(StudentAccount $link): \Illuminate\Database\Eloquent\Builder
    {
        return SchoolAssignment::query()
            ->where('status','published')
            ->whereHas('teachingAssignment',fn($q)=>$q
                ->whereIn('class_section_id',$this->studentEnrollments($link)->select('class_section_id'))
                ->whereHas('teacherProfile',fn($t)=>$t
                    ->where('school_id',$link->student->school_id)
                    ->whereHas('campus',fn($c)=>$c->where('school_id',$link->student->school_id)))
                ->whereHas('subject',fn($s)=>$s->where('school_id',$link->student->school_id)));
    }

    public function studentIndex(): View
    {
        $link=$this->linkedStudent();
        $assignments=$link
            ? $this->accessibleAssignments($link)->with('teachingAssignment.subject')->latest()->get()
            : collect();
        $enrollmentIds=$link ? $this->studentEnrollments($link)->pluck('id') : collect();
        $submissions=AssignmentSubmission::whereIn('class_enrollment_id',$enrollmentIds)
            ->whereIn('school_assignment_id',$assignments->pluck('id'))->get()
            ->keyBy('school_assignment_id');
        return view('assignments.student',compact('link','assignments','submissions'));
    }

    public function submit(Request $request, SchoolAssignment $assignment): RedirectResponse
    {
        $link=$this->linkedStudent();
        abort_unless($link,403);
        abort_unless($this->accessibleAssignments($link)->whereKey($assignment->id)->exists(),404);
        $enrollment=$this->studentEnrollments($link)
            ->where('class_section_id',$assignment->teachingAssignment->class_section_id)->firstOrFail();
        if ($assignment->due_at->isPast()) {
            throw ValidationException::withMessages(['response'=>'The submission deadline has passed.']);
        }
        $data=$request->validate(['response'=>['required','string','max:20000']]);
        $existing=AssignmentSubmission::where('school_assignment_id',$assignment->id)
            ->where('class_enrollment_id',$enrollment->id)->first();
        if ($existing && $existing->graded_at) {
            throw ValidationException::withMessages(['response'=>'Graded work cannot be resubmitted.']);
        }
        AssignmentSubmission::updateOrCreate(
            ['school_assignment_id'=>$assignment->id,'class_enrollment_id'=>$enrollment->id],
            ['response'=>$data['response'],'submitted_at'=>now(),'marks'=>null,'feedback'=>null,'graded_at'=>null]
        );
        return to_route('assignments.student')->with('success','Assignment submitted.');
    }

    public function studentLinks(): View
    {
        return view('assignments.student-links', [
            'students'=>\App\Models\Student::orderBy('first_name')->get(),
            'users'=>User::where('role','student')->orderBy('name')->get(),
            'links'=>StudentAccount::with('user','student')->get(),
        ]);
    }

    public function linkStudent(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'student_id'=>['required','integer',Rule::exists('students','id'),Rule::unique('student_accounts','student_id')],
            'user_id'=>['required','integer',Rule::exists('users','id')->where('role','student'),Rule::unique('student_accounts','user_id')],
        ]);
        StudentAccount::create($data);
        return to_route('student-links.index')->with('success','Student account linked.');
    }
}
