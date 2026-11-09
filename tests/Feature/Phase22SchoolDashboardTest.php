<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\ExamResult;
use App\Models\Examination;
use App\Models\FeeCharge;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\LibraryBook;
use App\Models\LibraryCopy;
use App\Models\LibraryLoan;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase22SchoolDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role,string $key): User
    {
        return User::create([
            'name'=>ucfirst($role).' '.$key,
            'email'=>$role.$key.'@phase22.test',
            'role'=>$role,'password'=>'safe-password-123',
        ]);
    }

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
            'name'=>'FYJC '.$code,'code'=>'FYJC',
        ]);
        $student=Student::create([
            'school_id'=>$school->id,'admission_number'=>'ADM'.$code,
            'first_name'=>'Learner'.$code,'last_name'=>'Example',
            'admission_date'=>'2026-06-01','status'=>'active',
        ]);
        $enrollment=ClassEnrollment::create([
            'student_id'=>$student->id,'class_section_id'=>$section->id,
        ]);
        $subject=Subject::create(['school_id'=>$school->id,'name'=>'IT '.$code,'code'=>'IT']);
        $teacherUser=$this->user('teacher',$code);
        $teacher=TeacherProfile::create([
            'user_id'=>$teacherUser->id,'school_id'=>$school->id,
            'campus_id'=>$campus->id,'employee_code'=>'TCH'.$code,
        ]);
        StudentAttendance::create([
            'class_enrollment_id'=>$enrollment->id,'attendance_date'=>now()->toDateString(),
            'status'=>'present',
        ]);
        $exam=Examination::create([
            'class_section_id'=>$section->id,'subject_id'=>$subject->id,
            'name'=>'Unit '.$code,'exam_date'=>now()->toDateString(),
            'max_marks'=>50,'pass_marks'=>20,
        ]);
        ExamResult::create([
            'examination_id'=>$exam->id,'class_enrollment_id'=>$enrollment->id,
            'status'=>'graded','marks_obtained'=>40,
        ]);
        $fee=FeeStructure::create([
            'class_section_id'=>$section->id,'name'=>'Tuition '.$code,
            'amount_paise'=>10000,'due_date'=>now()->addDays(30)->toDateString(),
        ]);
        $charge=FeeCharge::create([
            'fee_structure_id'=>$fee->id,'class_enrollment_id'=>$enrollment->id,
            'amount_paise'=>10000,
        ]);
        $payee=$this->user('staff',$code);
        FeePayment::create([
            'fee_charge_id'=>$charge->id,'amount_paise'=>3000,
            'paid_on'=>now()->toDateString(),'method'=>'cash','recorded_by'=>$payee->id,
        ]);
        $book=LibraryBook::create(['school_id'=>$school->id,'title'=>'Book '.$code]);
        $copy=LibraryCopy::create([
            'library_book_id'=>$book->id,'barcode'=>'COPY'.$code,'status'=>'borrowed',
        ]);
        LibraryLoan::create([
            'library_copy_id'=>$copy->id,'class_enrollment_id'=>$enrollment->id,
            'issued_on'=>now()->toDateString(),'due_on'=>now()->addDays(7)->toDateString(),
            'issued_by'=>$payee->id,
        ]);

        return compact('school','student','enrollment','section','exam','fee','charge','book','copy');
    }

    private function member(User $user,School $school,string $role='school_admin'): void
    {
        SchoolMembership::create([
            'user_id'=>$user->id,'school_id'=>$school->id,'role'=>$role,
        ]);
    }

    public function test_dashboard_denies_guests_and_users_without_school_membership(): void
    {
        $a=$this->fixture('A');$b=$this->fixture('B');
        $url=route('school-dashboard.index',$a['school']);
        $this->get($url)->assertRedirect('/login');
        $admin=$this->user('admin','A');
        $this->actingAs($admin)->get($url)->assertForbidden();
        $this->member($admin,$a['school']);
        $this->get($url)->assertOk()->assertSee('School A');
        $this->get(route('school-dashboard.index',$b['school']))->assertForbidden();
        $this->get(route('school-dashboard.export',$b['school']))->assertForbidden();
    }

    public function test_dashboard_aggregates_only_matching_school_records(): void
    {
        $a=$this->fixture('C');$b=$this->fixture('D');
        $auditor=$this->user('staff','auditor');
        $this->member($auditor,$a['school'],'auditor');
        $response=$this->actingAs($auditor)->get(route('school-dashboard.index',$a['school']));
        $response->assertOk()->assertDontSee('LearnerD');
        $metrics=$response->viewData('metrics');
        $expected=[
            'students.total'=>1,'students.active'=>1,'students.enrolled'=>1,
            'teachers'=>1,'attendance.recorded'=>1,'attendance.rate'=>100.0,
            'attendance.attended'=>1,
            'exams.graded'=>1,'exams.passed'=>1,'exams.average_percent'=>80.0,
            'fees.charged_paise'=>10000,'fees.paid_paise'=>3000,
            'fees.outstanding_paise'=>7000,'fees.period_paid_paise'=>3000,
            'library.titles'=>1,
            'library.copies'=>1,'library.active_loans'=>1,
        ];
        foreach($expected as $key=>$value) {
            $this->assertEquals($value,data_get($metrics,$key),"Unexpected dashboard metric: {$key}");
        }
        $this->assertCount(1,$metrics['attendance']['daily']);
        $this->assertSame(1,$metrics['attendance']['daily'][0]['recorded']);
    }

    public function test_corrupt_cross_school_enrollment_does_not_inflate_dashboard(): void
    {
        $a=$this->fixture('E');$b=$this->fixture('F');
        $corrupt=ClassEnrollment::create([
            'student_id'=>$b['student']->id,'class_section_id'=>$a['section']->id,
        ]);
        StudentAttendance::create([
            'class_enrollment_id'=>$corrupt->id,'attendance_date'=>now()->toDateString(),
            'status'=>'absent',
        ]);
        ExamResult::create([
            'examination_id'=>$a['exam']->id,'class_enrollment_id'=>$corrupt->id,
            'status'=>'graded','marks_obtained'=>0,
        ]);
        FeeCharge::create([
            'fee_structure_id'=>$a['fee']->id,'class_enrollment_id'=>$corrupt->id,
            'amount_paise'=>25000,
        ]);

        $admin=$this->user('admin','E');$this->member($admin,$a['school']);
        $response=$this->actingAs($admin)->get(route('school-dashboard.index',$a['school']));
        $response->assertOk();
        $metrics=$response->viewData('metrics');
        foreach([
            'students.enrolled'=>1,
            'attendance.recorded'=>1,
            'exams.graded'=>1,
            'fees.charged_paise'=>10000,
        ] as $key=>$expected) {
            $this->assertEquals($expected,data_get($metrics,$key),"Unexpected dashboard metric: {$key}");
        }
    }

    public function test_date_filters_validate_range_and_do_not_change_lifetime_balance(): void
    {
        $a=$this->fixture('G');
        $admin=$this->user('admin','G');$this->member($admin,$a['school']);
        $past=now()->subDays(100);
        $start=$past->toDateString();
        $end=$past->addDays(2)->toDateString();
        $this->actingAs($admin)->get(route('school-dashboard.index',$a['school']).'?start_date='.$start.'&end_date='.$end)
            ->assertOk()->assertViewHas('metrics',fn($m)=>
                $m['attendance']['recorded']===0
                && $m['exams']['graded']===0
                && $m['fees']['period_paid_paise']===0
                && $m['fees']['outstanding_paise']===7000
            );
        $this->get(route('school-dashboard.index',$a['school']).'?start_date=2026-12-01&end_date=2026-01-01')
            ->assertSessionHasErrors('start_date');
        $this->get(route('school-dashboard.index',$a['school']).'?start_date=2020-01-01&end_date=2026-01-01')
            ->assertSessionHasErrors('start_date');
    }

    public function test_export_only_contains_school_aggregates_and_escapes_formula_text(): void
    {
        $a=$this->fixture('H');
        $a['school']->update(['name'=>'=SUM(1,2)']);
        $admin=$this->user('admin','H');$this->member($admin,$a['school']);
        $response=$this->actingAs($admin)->get(route('school-dashboard.export',$a['school']));
        $response->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
        $csv=$response->streamedContent();
        $this->assertStringContainsString("'=SUM(1,2)",$csv);
        $this->assertStringContainsString('"Students (total)",1',$csv);
        $this->assertStringNotContainsString('LearnerH',$csv);
        $this->assertStringNotContainsString('ADMH',$csv);
    }
}
