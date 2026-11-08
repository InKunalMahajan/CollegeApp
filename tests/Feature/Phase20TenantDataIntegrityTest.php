<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\Examination;
use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\LibraryBook;
use App\Models\LibraryCopy;
use App\Models\LibraryLoan;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase20TenantDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $code): array
    {
        $school=School::create(['name'=>'School '.$code,'code'=>$code]);
        $campus=Campus::create(['school_id'=>$school->id,'name'=>'Campus','code'=>'MAIN']);
        $year=AcademicYear::create([
            'school_id'=>$school->id,'name'=>'2026-27',
            'starts_on'=>'2026-06-01','ends_on'=>'2027-05-31',
        ]);
        $section=ClassSection::create([
            'campus_id'=>$campus->id,'academic_year_id'=>$year->id,
            'name'=>'Class '.$code,'code'=>'A',
        ]);
        $student=Student::create([
            'school_id'=>$school->id,'admission_number'=>'ADM'.$code,
            'first_name'=>'Learner'.$code,'last_name'=>'Sample','admission_date'=>'2026-06-01',
        ]);
        $enrollment=ClassEnrollment::create([
            'student_id'=>$student->id,'class_section_id'=>$section->id,
        ]);
        return compact('school','campus','year','section','student','enrollment');
    }

    private function admin(School $school): User
    {
        $admin=User::create([
            'name'=>'School Admin','email'=>'admin'.(string)$school->id.'@phase20.test',
            'role'=>'admin','password'=>'safe-password-123',
        ]);
        SchoolMembership::create([
            'school_id'=>$school->id,'user_id'=>$admin->id,'role'=>'school_admin',
        ]);
        return $admin;
    }

    public function test_malformed_foreign_student_enrollment_is_hidden_and_rejected(): void
    {
        $a=$this->fixture('A');
        $b=$this->fixture('B');
        // An existing corrupted row references a school B student from school A's class.
        $corrupt=ClassEnrollment::create([
            'student_id'=>$b['student']->id,'class_section_id'=>$a['section']->id,
        ]);
        StudentAttendance::create([
            'class_enrollment_id'=>$corrupt->id,'attendance_date'=>'2026-09-01','status'=>'present',
        ]);
        $this->actingAs($this->admin($a['school']));
        $this->get(route('school-portal.index',$a['school']))
            ->assertOk()->assertSee('ADMA')->assertDontSee('ADMB');
        $this->get(route('school-academics.index',$a['school']))
            ->assertOk()->assertSee('LearnerA')->assertDontSee('LearnerB');
        $this->get(route('school-security.report',$a['school']))
            ->assertOk()->assertViewHas('attendance',fn($attendance)=>
                (int)$attendance->recorded===0
            );
        $this->post(route('school-academics.attendance',$a['school']),[
            'class_enrollment_id'=>$corrupt->id,
            'attendance_date'=>'2026-09-02','status'=>'present',
        ])->assertNotFound();

        $exam=Examination::create([
            'class_section_id'=>$a['section']->id,'subject_id'=>$this->subject($a['school'])->id,
            'name'=>'Unit Test','exam_date'=>'2026-10-01',
            'max_marks'=>50,'pass_marks'=>20,
        ]);
        $this->post(route('school-academics.results',[$a['school'],$exam]),[
            'class_enrollment_id'=>$corrupt->id,
            'status'=>'graded','marks_obtained'=>45,
        ])->assertNotFound();
        $this->assertDatabaseCount('exam_results',0);
    }

    private function subject(School $school): \App\Models\Subject
    {
        return \App\Models\Subject::create([
            'school_id'=>$school->id,'name'=>'Math','code'=>'MAT',
        ]);
    }

    public function test_corrupt_fee_structure_or_foreign_student_is_not_payable_or_visible(): void
    {
        $a=$this->fixture('C');
        $b=$this->fixture('D');
        $corrupt=ClassEnrollment::create([
            'student_id'=>$b['student']->id,'class_section_id'=>$a['section']->id,
        ]);
        $feeA=FeeStructure::create([
            'class_section_id'=>$a['section']->id,'name'=>'Fee A',
            'due_date'=>'2026-12-01','amount_paise'=>10000,
        ]);
        $foreignCharge=FeeCharge::create([
            'fee_structure_id'=>$feeA->id,'class_enrollment_id'=>$corrupt->id,
            'amount_paise'=>10000,
        ]);
        $feeOther=FeeStructure::create([
            'class_section_id'=>$b['section']->id,'name'=>'Fee B',
            'due_date'=>'2026-12-01','amount_paise'=>10000,
        ]);
        $mismatchedCharge=FeeCharge::create([
            'fee_structure_id'=>$feeOther->id,
            'class_enrollment_id'=>$a['enrollment']->id,'amount_paise'=>10000,
        ]);

        $this->actingAs($this->admin($a['school']));
        $this->get(route('school-finance.fees',$a['school']))->assertOk()
            ->assertDontSee('Fee A')->assertDontSee('Fee B');
        $this->get(route('school-portal.index',$a['school']))->assertOk()
            ->assertDontSee('Fee A')->assertDontSee('Fee B');
        $this->get(route('school-security.report',$a['school']))->assertOk()
            ->assertViewHas('chargedPaise',0);
        foreach ([$foreignCharge,$mismatchedCharge] as $charge) {
            $this->post(route('school-finance.payment',[$a['school'],$charge]),[
                'amount'=>'20.00','paid_on'=>'2026-10-01','method'=>'cash',
            ])->assertNotFound();
        }
        $this->assertDatabaseCount('fee_payments',0);
    }

    public function test_library_rejects_foreign_student_even_with_local_copy_and_section(): void
    {
        $a=$this->fixture('E');
        $b=$this->fixture('F');
        $corrupt=ClassEnrollment::create([
            'student_id'=>$b['student']->id,'class_section_id'=>$a['section']->id,
        ]);
        $book=LibraryBook::create([
            'school_id'=>$a['school']->id,'title'=>'Shared Shelf',
        ]);
        $copy=LibraryCopy::create([
            'library_book_id'=>$book->id,'barcode'=>'COPY-E','status'=>'available',
        ]);
        $loan=LibraryLoan::create([
            'library_copy_id'=>$copy->id,'class_enrollment_id'=>$corrupt->id,
            'issued_on'=>'2026-10-01','due_on'=>'2026-12-01',
            'issued_by'=>$this->admin($a['school'])->id,
        ]);
        $this->actingAs(User::where('email','admin'.$a['school']->id.'@phase20.test')->firstOrFail());
        $this->get(route('school-finance.library',$a['school']))
            ->assertOk()->assertViewHas('loans',fn($loans)=>$loans->total()===0);
        $this->post(route('school-finance.issue',$a['school']),[
            'library_copy_id'=>$copy->id,'class_enrollment_id'=>$corrupt->id,
            'due_on'=>now()->addDays(5)->toDateString(),
        ])->assertNotFound();
        $this->post(route('school-finance.return',[$a['school'],$loan]))
            ->assertNotFound();
        $this->assertNull($loan->fresh()->returned_on);
    }
}
