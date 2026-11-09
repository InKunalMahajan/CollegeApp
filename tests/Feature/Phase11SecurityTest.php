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
use App\Models\School;
use App\Models\SchoolAuditEvent;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $suffix): User
    {
        return User::create([
            'name' => $role.' '.$suffix,
            'email' => $role.$suffix.'@phase11.test',
            'role' => $role,
            'password' => 'secure-password-123',
        ]);
    }

    private function school(string $code): array
    {
        $school=School::create(['name'=>'School '.$code,'code'=>$code]);
        $campus=Campus::create(['school_id'=>$school->id,'name'=>'Main','code'=>'M']);
        $year=AcademicYear::create(['school_id'=>$school->id,'name'=>'2026-27','starts_on'=>'2026-06-01','ends_on'=>'2027-05-31']);
        $section=ClassSection::create(['campus_id'=>$campus->id,'academic_year_id'=>$year->id,'name'=>'FYJC A','code'=>'FYJC']);
        $student=Student::create(['school_id'=>$school->id,'admission_number'=>'A'.$code,'first_name'=>'Demo','last_name'=>$code,'admission_date'=>'2026-06-01']);
        $enrollment=ClassEnrollment::create(['student_id'=>$student->id,'class_section_id'=>$section->id]);
        return compact('school','section','enrollment');
    }

    public function test_school_report_fails_closed_and_does_not_leak_other_school_totals(): void
    {
        $a=$this->school('A');
        $b=$this->school('B');
        $admin=$this->user('admin','A');

        StudentAttendance::create(['class_enrollment_id'=>$a['enrollment']->id,'attendance_date'=>'2026-10-01','status'=>'present']);
        foreach (['2026-10-01','2026-10-02'] as $day) {
            StudentAttendance::create(['class_enrollment_id'=>$b['enrollment']->id,'attendance_date'=>$day,'status'=>'absent']);
        }

        $path='/schools/'.$a['school']->id.'/security-report';
        $this->get($path)->assertRedirect('/login');
        $this->actingAs($admin)->get($path)->assertForbidden();

        SchoolMembership::create(['school_id'=>$a['school']->id,'user_id'=>$admin->id,'role'=>'school_admin']);
        $this->get($path)->assertOk()->assertSee('School A')
            ->assertSee('1 present or late')
            ->assertDontSee('School B');
        $this->get('/schools/'.$b['school']->id.'/security-report')->assertForbidden();

        $teacher=$this->user('teacher','A');
        SchoolMembership::create(['school_id'=>$a['school']->id,'user_id'=>$teacher->id,'role'=>'auditor']);
        $this->actingAs($teacher)->get($path)->assertOk();
    }

    public function test_fee_payment_creates_school_scoped_audit_event(): void
    {
        $a=$this->school('C');
        $admin=$this->user('admin','C');
        $structure=FeeStructure::create(['class_section_id'=>$a['section']->id,'name'=>'Term fee',
            'due_date'=>'2026-12-01','amount_paise'=>50000]);
        $charge=FeeCharge::create(['fee_structure_id'=>$structure->id,
            'class_enrollment_id'=>$a['enrollment']->id,'amount_paise'=>50000]);

        $this->actingAs($admin)->post('/fees/charges/'.$charge->id.'/payments',[
            'amount'=>'100.50','paid_on'=>'2026-10-01','method'=>'cash',
        ])->assertRedirect(route('fees.index'));

        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,
            'actor_user_id'=>$admin->id,
            'action'=>'fee.payment.recorded',
            'subject_type'=>'fee_payment',
        ]);
        $this->assertDatabaseCount('school_audit_events',1);
    }

    public function test_book_issue_and_return_are_audited_but_duplicate_issue_is_not(): void
    {
        $a=$this->school('D');
        $admin=$this->user('admin','D');
        $book=LibraryBook::create(['school_id'=>$a['school']->id,'title'=>'Learning']);
        $copy=LibraryCopy::create(['library_book_id'=>$book->id,'barcode'=>'D001','status'=>'available']);
        $input=['library_copy_id'=>$copy->id,'class_enrollment_id'=>$a['enrollment']->id,
            'due_on'=>now()->addDays(5)->toDateString()];

        $this->actingAs($admin)->post('/library/loans',$input)->assertRedirect(route('library.index'));
        $this->post('/library/loans',$input)->assertSessionHasErrors('library_copy_id');
        $this->assertDatabaseCount('school_audit_events',1);
        $loan=\App\Models\LibraryLoan::firstOrFail();
        $this->post('/library/loans/'.$loan->id.'/return')->assertRedirect(route('library.index'));
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,'action'=>'library.loan.returned','subject_id'=>$loan->id,
        ]);
        $this->assertDatabaseCount('school_audit_events',2);
    }
}
