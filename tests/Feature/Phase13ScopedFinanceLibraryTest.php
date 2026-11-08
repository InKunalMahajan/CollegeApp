<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\LibraryBook;
use App\Models\LibraryCopy;
use App\Models\LibraryLoan;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13ScopedFinanceLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $code): User
    {
        return User::create(['name'=>$role.' '.$code,'email'=>$role.$code.'@phase13.test',
            'password'=>'safe-password-123','role'=>$role]);
    }

    private function fixtures(string $code): array
    {
        $school=School::create(['name'=>'School '.$code,'code'=>$code]);
        $campus=Campus::create(['school_id'=>$school->id,'name'=>'Main','code'=>'MAIN']);
        $year=AcademicYear::create(['school_id'=>$school->id,'name'=>'2026-27','starts_on'=>'2026-06-01','ends_on'=>'2027-05-31']);
        $section=ClassSection::create(['campus_id'=>$campus->id,'academic_year_id'=>$year->id,'name'=>'FYJC','code'=>'FYJC']);
        $student=Student::create(['admission_number'=>'ADM'.$code,'first_name'=>'Learner'.$code,'last_name'=>'Demo','admission_date'=>'2026-06-01']);
        $enrollment=ClassEnrollment::create(['student_id'=>$student->id,'class_section_id'=>$section->id]);
        $structure=FeeStructure::create(['class_section_id'=>$section->id,'name'=>'Term '.$code,'due_date'=>'2026-12-01','amount_paise'=>100000]);
        $charge=FeeCharge::create(['fee_structure_id'=>$structure->id,'class_enrollment_id'=>$enrollment->id,'amount_paise'=>100000]);
        $book=LibraryBook::create(['school_id'=>$school->id,'title'=>'Book '.$code]);
        $copy=LibraryCopy::create(['library_book_id'=>$book->id,'barcode'=>'COPY'.$code,'status'=>'available']);
        return compact('school','section','enrollment','charge','book','copy');
    }

    public function test_read_access_requires_membership_and_excludes_other_school_records(): void
    {
        $a=$this->fixtures('A');
        $b=$this->fixtures('B');
        $admin=$this->user('admin','A');

        $this->get(route('school-finance.fees',$a['school']))->assertRedirect('/login');
        $this->actingAs($admin)->get(route('school-finance.fees',$a['school']))->assertForbidden();

        SchoolMembership::create(['school_id'=>$a['school']->id,'user_id'=>$admin->id,'role'=>'school_admin']);

        $this->get(route('school-finance.fees',$a['school']))->assertOk()
            ->assertSee('LearnerA')->assertDontSee('LearnerB');
        $this->get(route('school-finance.library',$a['school']))->assertOk()
            ->assertSee('Book A')->assertDontSee('Book B');
        $this->get(route('school-finance.fees',$b['school']))->assertForbidden();
        $this->get(route('school-finance.library',$b['school']))->assertForbidden();
    }

    public function test_scoped_payment_rejects_cross_school_charge_and_records_audit(): void
    {
        $a=$this->fixtures('C');
        $b=$this->fixtures('D');
        $admin=$this->user('admin','C');
        SchoolMembership::create(['school_id'=>$a['school']->id,'user_id'=>$admin->id,'role'=>'school_admin']);
        $this->actingAs($admin);

        $input=['amount'=>'100.50','paid_on'=>'2026-10-01','method'=>'cash'];
        $this->post(route('school-finance.payment',[$a['school'],$b['charge']]),$input)->assertNotFound();
        $this->post(route('school-finance.payment',[$a['school'],$a['charge']]),$input)
            ->assertRedirect(route('school-finance.fees',$a['school']));
        $this->assertDatabaseCount('fee_payments',1);
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,'action'=>'fee.payment.recorded',
        ]);
    }

    public function test_scoped_borrowing_rejects_external_copy_and_enrollment(): void
    {
        $a=$this->fixtures('E');
        $b=$this->fixtures('F');
        $admin=$this->user('admin','E');
        SchoolMembership::create(['school_id'=>$a['school']->id,'user_id'=>$admin->id,'role'=>'school_admin']);
        $this->actingAs($admin);
        $input=['library_copy_id'=>$a['copy']->id,'class_enrollment_id'=>$a['enrollment']->id,
            'due_on'=>now()->addDays(7)->toDateString()];

        $this->post(route('school-finance.issue',$a['school']),array_merge($input,[
            'library_copy_id'=>$b['copy']->id,
        ]))->assertNotFound();
        $this->post(route('school-finance.issue',$a['school']),array_merge($input,[
            'class_enrollment_id'=>$b['enrollment']->id,
        ]))->assertNotFound();

        $this->post(route('school-finance.issue',$a['school']),$input)
            ->assertRedirect(route('school-finance.library',$a['school']));
        $loan=LibraryLoan::firstOrFail();
        $this->post(route('school-finance.return',[$b['school'],$loan]))->assertForbidden();
        $this->post(route('school-finance.return',[$a['school'],$loan]))
            ->assertRedirect(route('school-finance.library',$a['school']));
        $this->assertDatabaseCount('school_audit_events',2);
    }

    public function test_auditor_has_no_financial_write_access(): void
    {
        $a=$this->fixtures('G');
        $auditor=$this->user('staff','G');
        SchoolMembership::create(['school_id'=>$a['school']->id,'user_id'=>$auditor->id,'role'=>'auditor']);
        $this->actingAs($auditor)
            ->get(route('school-finance.fees',$a['school']))->assertForbidden();
        $this->post(route('school-finance.payment',[$a['school'],$a['charge']]),[])->assertForbidden();
    }
}
