<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\FeeCharge;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\LibraryBook;
use App\Models\LibraryCopy;
use App\Models\LibraryLoan;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase9FeesLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $role): User {
        return User::create([
            'name'=>ucfirst($role), 'email'=>$role.'@phase9.test',
            'password'=>'secure-password-123','role'=>$role,
        ]);
    }

    private function enrollment(string $code): array {
        $school=School::create(['name'=>'School '.$code,'code'=>$code]);
        $campus=Campus::create(['school_id'=>$school->id,'name'=>'Main','code'=>'MAIN']);
        $year=AcademicYear::create(['school_id'=>$school->id,'name'=>'2026-27',
            'starts_on'=>'2026-06-01','ends_on'=>'2027-05-31']);
        $section=ClassSection::create(['campus_id'=>$campus->id,'academic_year_id'=>$year->id,
            'name'=>'FYJC','code'=>'FYJC']);
        $student=Student::create(['admission_number'=>'ADM'.$code,'first_name'=>'Demo',
            'last_name'=>'Student','admission_date'=>'2026-06-01']);
        $enrollment=ClassEnrollment::create(['class_section_id'=>$section->id,'student_id'=>$student->id]);
        return compact('school','section','enrollment');
    }

    public function test_only_admin_can_manage_fees_and_library(): void {
        $this->get('/fees')->assertRedirect('/login');
        $this->get('/library')->assertRedirect('/login');
        foreach(['teacher','staff','student'] as $role) {
            $this->actingAs($this->role($role))
                ->get('/fees')->assertForbidden();
            $this->get('/library')->assertForbidden();
            $this->post('/library/books',[])->assertForbidden();
            $this->post('/fees/structures',[])->assertForbidden();
        }
    }

    public function test_fee_charge_is_idempotent_and_overpayment_rejected(): void {
        $data=$this->enrollment('A');
        $this->actingAs($this->role('admin'));
        $this->post('/fees/structures', [
            'class_section_id'=>$data['section']->id,'name'=>'Term 1',
            'amount'=>'1250.50','due_date'=>'2026-12-01',
        ])->assertRedirect(route('fees.index'));
        $structure=FeeStructure::firstOrFail();
        $this->assertSame(125050, $structure->amount_paise);
        $this->post('/fees/structures/'.$structure->id.'/issue')
            ->assertRedirect(route('fees.index'));
        $this->post('/fees/structures/'.$structure->id.'/issue')
            ->assertRedirect(route('fees.index'));
        $this->assertDatabaseCount('fee_charges',1);
        $charge=FeeCharge::firstOrFail();
        $this->post('/fees/charges/'.$charge->id.'/payments', [
            'amount'=>'250.50','paid_on'=>'2026-10-01','method'=>'upi','reference'=>'TXA001',
        ])->assertRedirect(route('fees.index'));
        $this->assertSame(25050, FeePayment::firstOrFail()->amount_paise);
        $this->post('/fees/charges/'.$charge->id.'/payments', [
            'amount'=>'1000.01','paid_on'=>'2026-10-01','method'=>'cash',
        ])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('fee_payments',1);
        $this->get('/fees/receipts/'.FeePayment::firstOrFail()->id)->assertOk()->assertSee('250.50');
    }

    public function test_fee_payment_validation_rejects_fractional_and_invalid_amounts(): void {
        $data=$this->enrollment('B');
        $charge=FeeCharge::create([
            'fee_structure_id'=>FeeStructure::create([
                'class_section_id'=>$data['section']->id,'name'=>'Annual',
                'amount_paise'=>50000,'due_date'=>'2026-12-01',
            ])->id,'class_enrollment_id'=>$data['enrollment']->id,'amount_paise'=>50000,
        ]);
        $this->actingAs($this->role('admin'));
        foreach(['2.345','-5','0.00'] as $amount) {
            $this->post('/fees/charges/'.$charge->id.'/payments',[
                'amount'=>$amount,'paid_on'=>'2026-10-01','method'=>'cash',
            ])->assertSessionHasErrors('amount');
        }
        $this->assertDatabaseCount('fee_payments',0);
    }

    public function test_library_borrow_return_and_duplicate_loan_blocked(): void {
        $data=$this->enrollment('C');
        $this->actingAs($this->role('admin'));
        $this->post('/library/books', ['school_id'=>$data['school']->id,'title'=>'Algebra',
            'author'=>'Author'])->assertRedirect(route('library.index'));
        $book=LibraryBook::firstOrFail();
        $this->post('/library/copies',[
            'library_book_id'=>$book->id,'barcode'=>'COPY-001',
        ])->assertRedirect(route('library.index'));
        $copy=LibraryCopy::firstOrFail();
        $input=['library_copy_id'=>$copy->id,'class_enrollment_id'=>$data['enrollment']->id,
            'due_on'=>now()->addDays(10)->toDateString()];
        $this->post('/library/loans',$input)->assertRedirect(route('library.index'));
        $this->post('/library/loans',$input)->assertSessionHasErrors('library_copy_id');
        $this->assertDatabaseCount('library_loans',1);
        $loan=LibraryLoan::firstOrFail();
        $this->post('/library/loans/'.$loan->id.'/return')->assertRedirect(route('library.index'));
        $this->assertSame('available',$copy->fresh()->status);
        $this->post('/library/loans/'.$loan->id.'/return')->assertSessionHasErrors('loan');
        $this->post('/library/loans',$input)->assertRedirect(route('library.index'));
        $this->assertDatabaseCount('library_loans',2);
    }

    public function test_library_rejects_cross_school_borrower(): void {
        $a=$this->enrollment('D');$b=$this->enrollment('E');
        $book=LibraryBook::create(['school_id'=>$a['school']->id,'title'=>'English']);
        $copy=LibraryCopy::create(['library_book_id'=>$book->id,'barcode'=>'COPY-002','status'=>'available']);
        $this->actingAs($this->role('admin'))->post('/library/loans',[
            'library_copy_id'=>$copy->id,
            'class_enrollment_id'=>$b['enrollment']->id,
            'due_on'=>now()->addWeek()->toDateString(),
        ])->assertSessionHasErrors('class_enrollment_id');
        $this->assertDatabaseCount('library_loans',0);
    }
}
