<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase16SchoolAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $suffix): User
    {
        return User::create([
            'name'=>ucfirst($role).' '.$suffix,
            'email'=>$role.$suffix.'@phase16.test',
            'password'=>'secure-password-123',
            'role'=>$role,
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
            'name'=>'FYJC '.$code,'code'=>'FYJC',
        ]);
        return compact('school','section');
    }

    private function membership(User $user, School $school, string $role): SchoolMembership
    {
        return SchoolMembership::create([
            'school_id'=>$school->id,'user_id'=>$user->id,'role'=>$role,
        ]);
    }

    public function test_school_navigation_shows_only_memberships_and_protects_administration(): void
    {
        $a=$this->school('A');$b=$this->school('B');
        $admin=$this->user('admin','A');
        $this->get('/my-schools')->assertRedirect('/login');
        $this->actingAs($admin);
        $this->get('/my-schools')->assertOk()->assertDontSee('School A')->assertDontSee('School B');
        $this->get(route('school-admin.index',$a['school']))->assertForbidden();

        $this->membership($admin,$a['school'],'school_admin');
        $this->get('/my-schools')->assertOk()->assertSee('School A')->assertDontSee('School B');
        $this->get(route('school-admin.index',$a['school']))->assertOk();
        $this->get(route('school-admin.index',$b['school']))->assertForbidden();
    }

    public function test_admin_can_add_and_revoke_auditor_only_in_own_school(): void
    {
        $a=$this->school('C');$b=$this->school('D');
        $admin=$this->user('admin','C');
        $auditor=$this->user('staff','C');
        $this->membership($admin,$a['school'],'school_admin');
        $this->actingAs($admin);

        $this->post(route('school-admin.auditors.store',$a['school']),[
            'email'=>$auditor->email,'role'=>'school_admin',
        ])->assertRedirect(route('school-admin.index',$a['school']));
        $record=SchoolMembership::where('school_id',$a['school']->id)
            ->where('user_id',$auditor->id)->firstOrFail();
        $this->assertSame('auditor',$record->role);
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,'action'=>'school.membership.auditor_added',
        ]);
        $this->actingAs($auditor)->get(route('school-admin.index',$a['school']))->assertForbidden();
        $this->get(route('school-portal.index',$a['school']))->assertOk();
        $this->actingAs($admin)
            ->delete(route('school-admin.auditors.destroy',[$b['school'],$record]))->assertForbidden();

        $otherAdmin=$this->membership($this->user('admin','D'),$a['school'],'school_admin');
        $this->delete(route('school-admin.auditors.destroy',[$a['school'],$otherAdmin]))
            ->assertNotFound();
        $this->delete(route('school-admin.auditors.destroy',[$a['school'],$record]))
            ->assertRedirect(route('school-admin.index',$a['school']));
        $this->assertDatabaseMissing('school_memberships',['id'=>$record->id]);
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,'action'=>'school.membership.auditor_removed',
        ]);
    }

    public function test_ownership_claim_requires_only_matching_school_enrollments(): void
    {
        $a=$this->school('E');$b=$this->school('F');
        $admin=$this->user('admin','E');
        $this->membership($admin,$a['school'],'school_admin');
        $this->actingAs($admin);

        $valid=Student::create([
            'admission_number'=>'LEG-E','first_name'=>'Valid','last_name'=>'Student',
            'admission_date'=>'2026-10-01',
        ]);
        ClassEnrollment::create(['student_id'=>$valid->id,'class_section_id'=>$a['section']->id]);
        $this->post(route('school-admin.students.claim',$a['school']),[
            'admission_number'=>'LEG-E',
        ])->assertRedirect(route('school-admin.index',$a['school']));
        $this->assertSame($a['school']->id,$valid->fresh()->school_id);

        $unlinked=Student::create([
            'admission_number'=>'UNLINKED-E','first_name'=>'Unknown',
            'last_name'=>'Student','admission_date'=>'2026-10-01',
        ]);
        $this->post(route('school-admin.students.claim',$a['school']),[
            'admission_number'=>'UNLINKED-E',
        ])->assertSessionHasErrors('admission_number');
        $this->assertNull($unlinked->fresh()->school_id);

        $foreign=Student::create([
            'admission_number'=>'LEG-F','first_name'=>'Other',
            'last_name'=>'School','admission_date'=>'2026-10-01',
        ]);
        ClassEnrollment::create(['student_id'=>$foreign->id,'class_section_id'=>$b['section']->id]);
        $this->post(route('school-admin.students.claim',$a['school']),[
            'admission_number'=>'LEG-F',
        ])->assertSessionHasErrors('admission_number');
        $this->assertNull($foreign->fresh()->school_id);

        $mixed=Student::create([
            'admission_number'=>'MIX-EF','first_name'=>'Mixed',
            'last_name'=>'Student','admission_date'=>'2026-10-01',
        ]);
        ClassEnrollment::create(['student_id'=>$mixed->id,'class_section_id'=>$a['section']->id]);
        ClassEnrollment::create(['student_id'=>$mixed->id,'class_section_id'=>$b['section']->id]);
        $this->post(route('school-admin.students.claim',$a['school']),[
            'admission_number'=>'MIX-EF',
        ])->assertSessionHasErrors('admission_number');

        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,'action'=>'academics.student.legacy_claimed',
            'subject_id'=>$valid->id,
        ]);
    }

    public function test_auditor_cannot_grant_membership_or_claim_student(): void
    {
        $a=$this->school('G');
        $auditor=$this->user('teacher','auditor');
        $this->membership($auditor,$a['school'],'auditor');
        $this->actingAs($auditor);
        $this->get('/my-schools')->assertOk();
        $this->post(route('school-admin.auditors.store',$a['school']),[
            'email'=>$this->user('staff','target')->email,
        ])->assertForbidden();
        $this->post(route('school-admin.students.claim',$a['school']),[
            'admission_number'=>'UNKNOWN',
        ])->assertForbidden();
    }
}
