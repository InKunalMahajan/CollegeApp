<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\LibraryBook;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase12SchoolPortalTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $suffix): User
    {
        return User::create([
            'name' => $role.' '.$suffix,
            'email' => $role.$suffix.'@phase12.test',
            'role' => $role,
            'password' => 'secure-password-123',
        ]);
    }

    private function school(string $code): array
    {
        $school=School::create(['name'=>'School '.$code,'code'=>$code]);
        $campus=Campus::create(['school_id'=>$school->id,'name'=>'Campus','code'=>'MAIN']);
        $year=AcademicYear::create(['school_id'=>$school->id,'name'=>'2026-27',
            'starts_on'=>'2026-06-01','ends_on'=>'2027-05-31']);
        $section=ClassSection::create(['campus_id'=>$campus->id,
            'academic_year_id'=>$year->id,'name'=>'FYJC','code'=>'FYJC']);
        $student=Student::create(['admission_number'=>'ADM'.$code,
            'first_name'=>'Learner'.$code,'last_name'=>'Sample','admission_date'=>'2026-06-01']);
        $enrollment=ClassEnrollment::create(['student_id'=>$student->id,'class_section_id'=>$section->id]);
        $teacher=$this->user('teacher',$code);
        TeacherProfile::create(['user_id'=>$teacher->id,'school_id'=>$school->id,
            'campus_id'=>$campus->id,'employee_code'=>'EMP'.$code]);
        $structure=FeeStructure::create(['class_section_id'=>$section->id,'name'=>'Fees '.$code,
            'amount_paise'=>50000,'due_date'=>'2026-12-01']);
        FeeCharge::create(['fee_structure_id'=>$structure->id,
            'class_enrollment_id'=>$enrollment->id,'amount_paise'=>50000]);
        LibraryBook::create(['school_id'=>$school->id,'title'=>'Book '.$code]);
        return compact('school','section');
    }

    public function test_school_portal_does_not_expose_records_from_other_schools(): void
    {
        $a=$this->school('A');$b=$this->school('B');
        $admin=$this->user('admin','onlyA');
        $url=route('school-portal.index',$a['school']);
        $this->get($url)->assertRedirect('/login');
        $this->actingAs($admin)->get($url)->assertForbidden();

        SchoolMembership::create(['school_id'=>$a['school']->id,
            'user_id'=>$admin->id,'role'=>'school_admin']);

        $this->get($url)->assertOk()
            ->assertSee('ADMA')
            ->assertSee('EMPA')
            ->assertSee('Fees A')
            ->assertSee('Book A')
            ->assertDontSee('ADMB')
            ->assertDontSee('EMPB')
            ->assertDontSee('Fees B')
            ->assertDontSee('Book B');

        $this->get(route('school-portal.index',$b['school']))->assertForbidden();
    }

    public function test_auditor_can_read_but_unrelated_student_cannot(): void
    {
        $school=$this->school('C')['school'];
        $auditor=$this->user('staff','auditor');
        SchoolMembership::create(['school_id'=>$school->id,
            'user_id'=>$auditor->id,'role'=>'auditor']);

        $this->actingAs($auditor)->get(route('school-portal.index',$school))->assertOk();
        $this->actingAs($this->user('student','outsider'))
            ->get(route('school-portal.index',$school))->assertForbidden();
    }
}
