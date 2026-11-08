<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\SchoolAssignment;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase17SelfServiceIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role,string $code): User
    {
        return User::create([
            'name'=>$role.' '.$code,'email'=>$role.$code.'@phase17.test',
            'password'=>'safe-password-123','role'=>$role,
        ]);
    }

    private function fixtures(string $code): array
    {
        $school=School::create(['name'=>'School '.$code,'code'=>$code]);
        $campus=Campus::create(['school_id'=>$school->id,'name'=>'Main','code'=>'MAIN']);
        $year=AcademicYear::create([
            'school_id'=>$school->id,'name'=>'2026-27',
            'starts_on'=>'2026-06-01','ends_on'=>'2027-05-31',
        ]);
        $section=ClassSection::create([
            'campus_id'=>$campus->id,'academic_year_id'=>$year->id,
            'name'=>'FYJC '.$code,'code'=>'FYJC',
        ]);
        $subject=Subject::create(['school_id'=>$school->id,'name'=>'IT '.$code,'code'=>'IT']);
        $teacherUser=$this->user('teacher',$code);
        $teacher=TeacherProfile::create([
            'user_id'=>$teacherUser->id,'school_id'=>$school->id,
            'campus_id'=>$campus->id,'employee_code'=>'EMP'.$code,
        ]);
        $teaching=TeacherSubjectAssignment::create([
            'teacher_profile_id'=>$teacher->id,'class_section_id'=>$section->id,'subject_id'=>$subject->id,
        ]);
        $studentUser=$this->user('student',$code);
        $student=Student::create([
            'school_id'=>$school->id,'admission_number'=>'ADM'.$code,
            'first_name'=>'Learner'.$code,'last_name'=>'Demo','admission_date'=>'2026-06-01',
        ]);
        $enrollment=ClassEnrollment::create(['student_id'=>$student->id,'class_section_id'=>$section->id]);
        StudentAccount::create(['student_id'=>$student->id,'user_id'=>$studentUser->id]);
        $assignment=SchoolAssignment::create([
            'teacher_subject_assignment_id'=>$teaching->id,'title'=>'Work '.$code,
            'instructions'=>'Do the work','due_at'=>now()->addDays(5),
            'max_marks'=>30,'status'=>'published',
        ]);
        return compact('school','campus','year','section','subject','teacher','teacherUser',
            'teaching','student','studentUser','enrollment','assignment');
    }

    public function test_student_only_sees_and_submits_own_school_assignments(): void
    {
        $a=$this->fixtures('A');
        $b=$this->fixtures('B');
        $this->actingAs($a['studentUser'])->get(route('assignments.student'))
            ->assertOk()->assertSee('Work A')->assertDontSee('Work B');

        $this->post(route('assignments.submit',$b['assignment']),['response'=>'Foreign'])
            ->assertNotFound();
        $this->post(route('assignments.submit',$a['assignment']),['response'=>'My answer'])
            ->assertRedirect(route('assignments.student'));
        $this->assertDatabaseCount('assignment_submissions',1);
        $this->assertSame($a['enrollment']->id,AssignmentSubmission::firstOrFail()->class_enrollment_id);
    }

    public function test_unassigned_student_cannot_read_or_submit_assignments(): void
    {
        $a=$this->fixtures('C');
        $a['student']->update(['school_id'=>null]);
        $this->actingAs($a['studentUser'])->get(route('assignments.student'))
            ->assertOk()->assertDontSee('Work C');
        $this->post(route('assignments.submit',$a['assignment']),['response'=>'Not allowed'])
            ->assertForbidden();
        $this->assertDatabaseCount('assignment_submissions',0);
    }

    public function test_teacher_cannot_publish_view_or_grade_other_school_work(): void
    {
        $a=$this->fixtures('D');
        $b=$this->fixtures('E');
        $submission=AssignmentSubmission::create([
            'school_assignment_id'=>$b['assignment']->id,
            'class_enrollment_id'=>$b['enrollment']->id,
            'response'=>'Private answer','submitted_at'=>now(),
        ]);
        $this->actingAs($a['teacherUser'])
            ->get(route('assignments.teacher'))->assertOk()
            ->assertSee('Work D')->assertDontSee('Work E');
        $this->post(route('assignments.publish',$b['assignment']))->assertForbidden();
        $this->get(route('assignments.submissions',$b['assignment']))->assertForbidden();
        $this->post(route('assignments.grade',$submission),['marks'=>20])->assertForbidden();
        $this->assertNull($submission->fresh()->graded_at);
    }

    public function test_mismatched_teacher_school_assignment_does_not_grant_access(): void
    {
        $a=$this->fixtures('F');
        $b=$this->fixtures('G');
        $bad=TeacherSubjectAssignment::create([
            'teacher_profile_id'=>$a['teacher']->id,
            'class_section_id'=>$b['section']->id,'subject_id'=>$b['subject']->id,
        ]);
        $work=SchoolAssignment::create([
            'teacher_subject_assignment_id'=>$bad->id,'title'=>'Forged work',
            'instructions'=>'Private','due_at'=>now()->addDays(5),
            'max_marks'=>30,'status'=>'draft',
        ]);
        $this->actingAs($a['teacherUser'])->get(route('assignments.teacher'))
            ->assertOk()->assertDontSee('Forged work');
        $this->post(route('assignments.publish',$work))->assertForbidden();
        $this->post(route('assignments.store'),[
            'teacher_subject_assignment_id'=>$bad->id,'title'=>'Bypass',
            'instructions'=>'No','due_at'=>now()->addDays(6)->toDateTimeString(),
            'max_marks'=>20,
        ])->assertForbidden();
    }
}
