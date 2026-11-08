<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase19SchoolStudentsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role,string $code): User
    {
        return User::create([
            'name'=>$role.' '.$code,'email'=>$role.$code.'@phase19.test',
            'password'=>'secure-pass-123','role'=>$role,
        ]);
    }

    private function school(string $code): School
    {
        return School::create(['name'=>'School '.$code,'code'=>$code]);
    }

    private function student(School $school,string $number,string $status='active'): Student
    {
        return Student::create([
            'school_id'=>$school->id,'admission_number'=>$number,
            'first_name'=>'Learner'.$number,'last_name'=>'Demo',
            'admission_date'=>'2026-06-01','status'=>$status,
        ]);
    }

    private function member(User $user,School $school,string $role='school_admin'): void
    {
        SchoolMembership::create(['user_id'=>$user->id,'school_id'=>$school->id,'role'=>$role]);
    }

    private function payload(Student $student,array $updates=[]): array
    {
        return array_merge([
            'admission_number'=>$student->admission_number,
            'first_name'=>$student->first_name,
            'last_name'=>$student->last_name,
            'admission_date'=>'2026-06-01',
            'status'=>$student->status ?? 'active',
            'email'=>$student->email,
        ],$updates);
    }

    public function test_membership_and_role_are_required_for_student_management(): void
    {
        $school=$this->school('A');
        $student=$this->student($school,'A100');
        $admin=$this->user('admin','A');
        $auditor=$this->user('staff','A');

        $this->get(route('school-students.index',$school))->assertRedirect('/login');
        $this->actingAs($admin)->get(route('school-students.index',$school))->assertForbidden();
        $this->member($admin,$school);
        $this->member($auditor,$school,'auditor');
        $this->actingAs($auditor)->get(route('school-students.index',$school))->assertForbidden();
        $this->get(route('school-students.edit',[$school,$student]))->assertForbidden();
        $this->put(route('school-students.update',[$school,$student]),$this->payload($student))
            ->assertForbidden();

        $this->actingAs($admin)->get(route('school-students.index',$school))
            ->assertOk()->assertSee('LearnerA100');
    }

    public function test_school_scope_applies_to_list_show_edit_and_update_even_with_membership_in_both_schools(): void
    {
        $a=$this->school('B');$b=$this->school('C');
        $owned=$this->student($a,'OWN-B');
        $foreign=$this->student($b,'PRIVATE-C');
        $admin=$this->user('admin','B');
        $this->member($admin,$a);
        $this->member($admin,$b);
        $this->actingAs($admin)->get(route('school-students.index',$a))
            ->assertOk()->assertSee('OWN-B')->assertDontSee('PRIVATE-C');

        $this->get(route('school-students.show',[$a,$foreign]))->assertNotFound();
        $this->get(route('school-students.edit',[$a,$foreign]))->assertNotFound();
        $this->put(route('school-students.update',[$a,$foreign]),$this->payload($foreign,[
            'first_name'=>'Stolen',
        ]))->assertNotFound();

        $this->assertSame('LearnerPRIVATE-C',$foreign->fresh()->first_name);
        $this->get(route('school-students.show',[$a,$owned]))->assertOk();
        $this->get(route('school-students.edit',[$a,$owned]))->assertOk();
    }

    public function test_search_status_and_pagination_are_scoped(): void
    {
        $a=$this->school('D');$b=$this->school('E');
        $this->student($a,'ACTIVE-D');
        $this->student($a,'GRAD-D','graduated');
        $this->student($b,'PRIVATE-E');
        for($i=0;$i<16;$i++){
            $this->student($a,'LIST-'.$i,'inactive');
        }
        $admin=$this->user('admin','D');
        $this->member($admin,$a);
        $this->actingAs($admin)->get(route('school-students.index',$a).'?search=GRAD')
            ->assertOk()->assertSee('GRAD-D')->assertDontSee('ACTIVE-D')->assertDontSee('PRIVATE-E');

        $this->get(route('school-students.index',$a).'?status=graduated')
            ->assertOk()->assertSee('GRAD-D')->assertDontSee('ACTIVE-D');

        $this->get(route('school-students.index',$a).'?status=inactive')
            ->assertOk()->assertSee('LIST-')->assertDontSee('PRIVATE-E');

        $this->get(route('school-students.index',$a).'?status=invalid')
            ->assertSessionHasErrors('status');
    }

    public function test_update_validates_fields_preserves_school_and_creates_audit_event(): void
    {
        $a=$this->school('F');$b=$this->school('G');
        $student=$this->student($a,'ID-F');
        $other=$this->student($b,'ID-G');
        $admin=$this->user('admin','F');
        $this->member($admin,$a);
        $this->actingAs($admin);

        $this->put(route('school-students.update',[$a,$student]),
            $this->payload($student,['admission_number'=>$other->admission_number]))
            ->assertSessionHasErrors('admission_number');

        $this->put(route('school-students.update',[$a,$student]),
            $this->payload($student,['status'=>'deleted']))
            ->assertSessionHasErrors('status');

        $this->put(route('school-students.update',[$a,$student]),$this->payload($student,[
            'first_name'=>'Revised','status'=>'graduated','school_id'=>$b->id,
        ]))->assertRedirect(route('school-students.show',[$a,$student]));

        $student->refresh();
        $this->assertSame($a->id,$student->school_id);
        $this->assertSame('Revised',$student->first_name);
        $this->assertSame('graduated',$student->status);
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a->id,'action'=>'academics.student.profile_updated',
            'subject_type'=>'student','subject_id'=>$student->id,
        ]);
        $this->assertDatabaseCount('students',2);
    }

    public function test_unassigned_legacy_student_cannot_be_edited_or_viewed(): void
    {
        $a=$this->school('H');
        $legacy=$this->student($a,'LEG-H');
        $legacy->update(['school_id'=>null]);
        $admin=$this->user('admin','H');
        $this->member($admin,$a);
        $this->actingAs($admin)->get(route('school-students.show',[$a,$legacy]))->assertNotFound();
        $this->put(route('school-students.update',[$a,$legacy]),
            $this->payload($legacy))->assertNotFound();
    }
}
