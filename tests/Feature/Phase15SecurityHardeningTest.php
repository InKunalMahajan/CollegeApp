<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase15SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $suffix): User
    {
        return User::create([
            'name' => ucfirst($role).' '.$suffix,
            'email' => $role.$suffix.'@phase15.test',
            'role' => $role,
            'password' => 'secure-password-123',
        ]);
    }

    private function school(string $code): array
    {
        $school = School::create(['name'=>'School '.$code,'code'=>$code]);
        $campus = Campus::create(['school_id'=>$school->id,'name'=>'Main','code'=>'MAIN']);
        $year = AcademicYear::create([
            'school_id'=>$school->id,'name'=>'2026-27',
            'starts_on'=>'2026-06-01','ends_on'=>'2027-05-31',
        ]);
        $section = ClassSection::create([
            'campus_id'=>$campus->id,'academic_year_id'=>$year->id,
            'name'=>'FYJC','code'=>'FYJC',
        ]);
        return compact('school','section');
    }

    public function test_strict_mode_blocks_legacy_admin_and_student_routes(): void
    {
        config()->set('school_security.legacy_global_routes_enabled', false);
        $admin=$this->user('admin','strict');
        $staff=$this->user('staff','strict');
        $this->actingAs($admin);
        foreach (['/fees','/library','/reports','/exams','/attendance','/users','/setup/schools','/teachers','/students'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->actingAs($staff)->get('/students')->assertForbidden();
    }

    public function test_explicit_development_override_keeps_old_routes_available(): void
    {
        config()->set('school_security.legacy_global_routes_enabled', true);
        $this->actingAs($this->user('admin','development'))
            ->get('/students')->assertOk();
    }

    public function test_school_admission_is_owned_and_cross_school_enrollment_fails_closed(): void
    {
        $a=$this->school('A');
        $b=$this->school('B');
        $admin=$this->user('admin','owned');
        $input=[
            'admission_number'=>'OWNED-A','first_name'=>'Local','last_name'=>'Student',
            'admission_date'=>'2026-10-01','status'=>'active',
        ];

        $this->actingAs($admin)
            ->post(route('school-admissions.store',$a['school']),$input)->assertForbidden();

        SchoolMembership::create([
            'school_id'=>$a['school']->id,'user_id'=>$admin->id,'role'=>'school_admin',
        ]);
        $this->post(route('school-admissions.store',$a['school']),$input)
            ->assertRedirect(route('school-academics.index',$a['school']));
        $student=Student::where('admission_number','OWNED-A')->firstOrFail();
        $this->assertSame($a['school']->id,$student->school_id);
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,'action'=>'academics.student.admitted',
            'subject_type'=>'student','subject_id'=>$student->id,
        ]);

        $this->post(route('school-academics.enroll',$a['school']),[
            'student_id'=>$student->id,'class_section_id'=>$a['section']->id,
        ])->assertRedirect(route('school-academics.index',$a['school']));

        SchoolMembership::create([
            'school_id'=>$b['school']->id,'user_id'=>$admin->id,'role'=>'school_admin',
        ]);
        $this->post(route('school-academics.enroll',$b['school']),[
            'student_id'=>$student->id,'class_section_id'=>$b['section']->id,
        ])->assertNotFound();
    }

    public function test_unassigned_legacy_student_cannot_enter_scoped_enrollment(): void
    {
        $a=$this->school('C');
        $admin=$this->user('admin','legacy');
        SchoolMembership::create([
            'school_id'=>$a['school']->id,'user_id'=>$admin->id,'role'=>'school_admin',
        ]);
        $unassigned=Student::create([
            'admission_number'=>'OLD-C','first_name'=>'Legacy','last_name'=>'Learner',
            'admission_date'=>'2026-10-01',
        ]);
        $this->actingAs($admin)->post(route('school-academics.enroll',$a['school']),[
            'student_id'=>$unassigned->id,'class_section_id'=>$a['section']->id,
        ])->assertNotFound();
        $this->assertDatabaseCount('class_enrollments',0);
    }
}
