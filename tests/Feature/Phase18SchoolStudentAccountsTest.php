<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\SchoolAssignment;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase18SchoolStudentAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role,string $name): User
    {
        return User::create([
            'name'=>ucfirst($role).' '.$name,
            'email'=>$role.$name.'@phase18.test',
            'role'=>$role,'password'=>'secure-pass-123',
        ]);
    }

    private function school(string $code): array
    {
        $school=School::create(['name'=>'School '.$code,'code'=>$code]);
        $campus=Campus::create(['school_id'=>$school->id,'name'=>'Main','code'=>'MAIN']);
        $year=AcademicYear::create([
            'school_id'=>$school->id,'name'=>'2026-27',
            'starts_on'=>'2026-06-01','ends_on'=>'2027-05-31',
        ]);
        $section=ClassSection::create([
            'campus_id'=>$campus->id,'academic_year_id'=>$year->id,
            'name'=>'FYJC','code'=>'FYJC',
        ]);
        $student=Student::create([
            'school_id'=>$school->id,'admission_number'=>'AD'.$code,
            'first_name'=>'Learner'.$code,'last_name'=>'Example','admission_date'=>'2026-06-01',
        ]);
        $enrollment=ClassEnrollment::create([
            'student_id'=>$student->id,'class_section_id'=>$section->id,
        ]);
        return compact('school','student','section','campus','enrollment');
    }

    private function member(User $user,School $school,string $role='school_admin'): void
    {
        SchoolMembership::create([
            'user_id'=>$user->id,'school_id'=>$school->id,'role'=>$role,
        ]);
    }

    public function test_only_school_admin_can_view_and_link_students_in_own_school(): void
    {
        $a=$this->school('A'); $b=$this->school('B');
        $admin=$this->user('admin','A');
        $auditor=$this->user('staff','auditor');
        $studentLogin=$this->user('student','A');

        $this->get(route('school-student-accounts.index',$a['school']))->assertRedirect('/login');
        $this->actingAs($admin)->get(route('school-student-accounts.index',$a['school']))->assertForbidden();
        $this->member($admin,$a['school']);
        $this->member($auditor,$a['school'],'auditor');

        $this->actingAs($auditor)->get(route('school-student-accounts.index',$a['school']))->assertForbidden();
        $this->post(route('school-student-accounts.store',$a['school']),[
            'student_id'=>$a['student']->id,'email'=>$studentLogin->email,
        ])->assertForbidden();

        $this->actingAs($admin)->get(route('school-student-accounts.index',$a['school']))
            ->assertOk()->assertSee('LearnerA')->assertDontSee('LearnerB');
        $this->get(route('school-student-accounts.index',$b['school']))->assertForbidden();

        $this->post(route('school-student-accounts.store',$a['school']),[
            'student_id'=>$b['student']->id,'email'=>$studentLogin->email,
        ])->assertNotFound();
        $this->assertDatabaseCount('student_accounts',0);

        $this->post(route('school-student-accounts.store',$a['school']),[
            'student_id'=>$a['student']->id,'email'=>$studentLogin->email,
        ])->assertRedirect(route('school-student-accounts.index',$a['school']));
        $this->assertDatabaseHas('student_accounts',[
            'student_id'=>$a['student']->id,'user_id'=>$studentLogin->id,
        ]);
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,'action'=>'student_account.linked',
        ]);
    }

    public function test_duplicate_student_or_login_and_non_student_role_are_rejected(): void
    {
        $a=$this->school('C'); $b=$this->school('D');
        $admin=$this->user('admin','C');
        $this->member($admin,$a['school']);
        $this->member($admin,$b['school']);
        $login=$this->user('student','C');
        $second=$this->user('student','D');
        $teacher=$this->user('teacher','C');

        $this->actingAs($admin)->post(route('school-student-accounts.store',$a['school']),[
            'student_id'=>$a['student']->id,'email'=>$teacher->email,
        ])->assertSessionHasErrors('email');

        $this->post(route('school-student-accounts.store',$a['school']),[
            'student_id'=>$a['student']->id,'email'=>$login->email,
        ])->assertRedirect(route('school-student-accounts.index',$a['school']));

        $this->post(route('school-student-accounts.store',$a['school']),[
            'student_id'=>$a['student']->id,'email'=>$second->email,
        ])->assertSessionHasErrors('email');

        $this->post(route('school-student-accounts.store',$b['school']),[
            'student_id'=>$b['student']->id,'email'=>$login->email,
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('student_accounts',1);
    }

    public function test_unlink_is_school_scoped_and_does_not_delete_submissions(): void
    {
        $a=$this->school('E'); $b=$this->school('F');
        $admin=$this->user('admin','E');
        $this->member($admin,$a['school']);
        $this->member($admin,$b['school']);
        $studentLogin=$this->user('student','E');
        $foreignLogin=$this->user('student','F');
        $ownLink=StudentAccount::create([
            'student_id'=>$a['student']->id,'user_id'=>$studentLogin->id,
        ]);
        $otherLink=StudentAccount::create([
            'student_id'=>$b['student']->id,'user_id'=>$foreignLogin->id,
        ]);
        $subject=Subject::create(['school_id'=>$a['school']->id,'name'=>'Math','code'=>'MATH']);
        $teacherUser=$this->user('teacher','E');
        $teacher=TeacherProfile::create([
            'user_id'=>$teacherUser->id,'school_id'=>$a['school']->id,
            'campus_id'=>$a['campus']->id,'employee_code'=>'TE',
        ]);
        $teaching=TeacherSubjectAssignment::create([
            'teacher_profile_id'=>$teacher->id,
            'class_section_id'=>$a['section']->id,
            'subject_id'=>$subject->id,
        ]);
        $assignment=SchoolAssignment::create([
            'teacher_subject_assignment_id'=>$teaching->id,
            'title'=>'History','instructions'=>'Answer',
            'due_at'=>now()->addDay(),'max_marks'=>20,'status'=>'published',
        ]);
        $submission=AssignmentSubmission::create([
            'school_assignment_id'=>$assignment->id,
            'class_enrollment_id'=>$a['enrollment']->id,
            'response'=>'Answer preserved','submitted_at'=>now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('school-student-accounts.destroy',[$a['school'],$otherLink]))
            ->assertNotFound();
        $this->assertDatabaseHas('student_accounts',['id'=>$otherLink->id]);

        $this->delete(route('school-student-accounts.destroy',[$a['school'],$ownLink]))
            ->assertRedirect(route('school-student-accounts.index',$a['school']));
        $this->assertDatabaseMissing('student_accounts',['id'=>$ownLink->id]);
        $this->assertDatabaseHas('assignment_submissions',['id'=>$submission->id]);
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,'action'=>'student_account.unlinked',
            'subject_id'=>$ownLink->id,
        ]);

        $this->actingAs($studentLogin)->get(route('assignments.student'))
            ->assertOk()->assertDontSee('History');
        $this->post(route('assignments.submit',$assignment),['response'=>'No longer linked'])
            ->assertForbidden();
    }

    public function test_unowned_legacy_student_cannot_be_linked(): void
    {
        $a=$this->school('G');
        $a['student']->update(['school_id'=>null]);
        $admin=$this->user('admin','G');
        $this->member($admin,$a['school']);
        $login=$this->user('student','G');

        $this->actingAs($admin)
            ->post(route('school-student-accounts.store',$a['school']),[
                'student_id'=>$a['student']->id,'email'=>$login->email,
            ])->assertNotFound();
        $this->assertDatabaseCount('student_accounts',0);
    }
}
