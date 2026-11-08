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
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase10ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $role.'@phase10.test',
            'role' => $role, 'password' => 'strong-password-123',
        ]);
    }

    private function fixtures(): array
    {
        $school = School::create(['name'=>'Example School','code'=>'S10']);
        $campus = Campus::create(['school_id'=>$school->id,'name'=>'Main','code'=>'MAIN']);
        $year = AcademicYear::create(['school_id'=>$school->id,'name'=>'2026-27',
            'starts_on'=>'2026-06-01','ends_on'=>'2027-05-31']);
        $section = ClassSection::create(['campus_id'=>$campus->id,'academic_year_id'=>$year->id,'name'=>'FYJC A','code'=>'A']);
        $subject = Subject::create(['school_id'=>$school->id,'name'=>'Mathematics','code'=>'MATH']);
        $student = Student::create(['admission_number'=>'S10','first_name'=>'Demo','last_name'=>'Learner','admission_date'=>'2026-06-01']);
        $enrollment = ClassEnrollment::create(['student_id'=>$student->id,'class_section_id'=>$section->id]);
        StudentAttendance::create(['class_enrollment_id'=>$enrollment->id,'attendance_date'=>'2026-10-01','status'=>'present']);
        StudentAttendance::create(['class_enrollment_id'=>$enrollment->id,'attendance_date'=>'2026-10-02','status'=>'absent']);
        $exam = Examination::create(['class_section_id'=>$section->id,'subject_id'=>$subject->id,'name'=>'Unit Test 1',
            'exam_date'=>'2026-10-02','max_marks'=>50,'pass_marks'=>20]);
        ExamResult::create(['examination_id'=>$exam->id,'class_enrollment_id'=>$enrollment->id,
            'status'=>'graded','marks_obtained'=>40]);
        $structure = FeeStructure::create(['class_section_id'=>$section->id,'name'=>'Term 1','due_date'=>'2026-10-31','amount_paise'=>100000]);
        $charge = FeeCharge::create(['fee_structure_id'=>$structure->id,'class_enrollment_id'=>$enrollment->id,'amount_paise'=>100000]);
        $admin=$this->user('admin');
        FeePayment::create(['fee_charge_id'=>$charge->id,'amount_paise'=>40000,'paid_on'=>'2026-10-02','method'=>'cash','recorded_by'=>$admin->id]);
        $book=LibraryBook::create(['school_id'=>$school->id,'title'=>'Numbers']);
        $copy=LibraryCopy::create(['library_book_id'=>$book->id,'barcode'=>'B10','status'=>'borrowed']);
        LibraryLoan::create(['library_copy_id'=>$copy->id,'class_enrollment_id'=>$enrollment->id,
            'issued_on'=>'2026-10-02','due_on'=>'2026-10-30','issued_by'=>$admin->id]);
        return compact('admin','section');
    }

    public function test_reports_and_exports_are_admin_only(): void
    {
        $url='/reports?start_date=2026-10-01&end_date=2026-10-31';
        $this->get($url)->assertRedirect('/login');
        foreach (['teacher','student','staff'] as $role) {
            $this->actingAs($this->user($role))->get($url)->assertForbidden();
            $this->get('/reports/export?type=fees&start_date=2026-10-01&end_date=2026-10-31')->assertForbidden();
        }
    }

    public function test_dashboard_totals_and_csv_exports(): void
    {
        $data=$this->fixtures();
        $params='start_date=2026-10-01&end_date=2026-10-31&class_section_id='.$data['section']->id;
        $this->actingAs($data['admin'])->get('/reports?'.$params)->assertOk()
            ->assertSee('50.00%')
            ->assertSee('80.00%')
            ->assertSee('600.00')
            ->assertSee('400.00');
        $response=$this->get('/reports/export?type=attendance&'.$params);
        $response->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
        $this->assertStringContainsString('FYJC A', $response->streamedContent());
        $this->get('/reports/export?type=fees&'.$params)->assertOk()
            ->assertHeader('content-type','text/csv; charset=UTF-8');
    }

    public function test_invalid_filter_and_export_type_are_rejected(): void
    {
        $admin=$this->user('admin');
        $this->actingAs($admin)->get('/reports?start_date=2026-11-30&end_date=2026-10-01')
            ->assertSessionHasErrors('end_date');
        $this->get('/reports/export?type=private&start_date=2026-10-01&end_date=2026-10-31')
            ->assertSessionHasErrors('type');
        $this->get('/reports/export?type=fees&class_section_id=9999&start_date=2026-10-01&end_date=2026-10-31')
            ->assertSessionHasErrors('class_section_id');
    }
}
