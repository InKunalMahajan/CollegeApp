<?php

namespace App\Http\Controllers;

use App\Models\AssignmentSubmission;
use App\Models\ClassEnrollment;
use App\Models\SchoolAssignment;
use App\Models\StudentAccount;
use App\Models\TeacherSubjectAssignment;
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
            ->whereHas('teacherProfile', fn ($q) => $q->where('user_id', auth()->id()));
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
            'submissions' => $assignment->submissions()->with('enrollment.student')->latest()->get(),
        ]);
    }

    public function grade(Request $request, AssignmentSubmission $submission): RedirectResponse
    {
        $assignment = $submission->assignment;
        $this->teachingAssignment($assignment);
        $data = $request->validate([
            'marks' => ['required','integer','min:0','max:'.$assignment->max_marks],
            'feedback' => ['nullable','string','max:5000'],
        ]);
        $submission->update($data + ['graded_at'=>now()]);
        return to_route('assignments.submissions', $assignment)->with('success','Submission graded.');
    }

    public function studentIndex(): View
    {
        $link = StudentAccount::where('user_id',auth()->id())->first();
        $enrollmentIds = $link ? ClassEnrollment::where('student_id',$link->student_id)->pluck('id') : collect();
        $classIds = $link ? ClassEnrollment::where('student_id',$link->student_id)->pluck('class_section_id') : collect();
        $assignments = SchoolAssignment::with('teachingAssignment.subject')
            ->where('status','published')
            ->whereHas('teachingAssignment', fn ($q) => $q->whereIn('class_section_id',$classIds))
            ->latest()->get();
        $submissions = AssignmentSubmission::whereIn('class_enrollment_id',$enrollmentIds)
            ->whereIn('school_assignment_id',$assignments->pluck('id'))->get()->keyBy('school_assignment_id');
        return view('assignments.student', compact('link','assignments','submissions'));
    }

    public function submit(Request $request, SchoolAssignment $assignment): RedirectResponse
    {
        if ($assignment->status !== 'published') abort(404);
        $link = StudentAccount::where('user_id', auth()->id())->firstOrFail();
        $enrollment = ClassEnrollment::where('student_id',$link->student_id)
            ->where('class_section_id',$assignment->teachingAssignment->class_section_id)->firstOrFail();
        if ($assignment->due_at->isPast()) {
            throw ValidationException::withMessages(['response'=>'The submission deadline has passed.']);
        }
        $data = $request->validate(['response'=>['required','string','max:20000']]);
        $existing = AssignmentSubmission::where('school_assignment_id',$assignment->id)
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
