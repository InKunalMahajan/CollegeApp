<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase39CampusExamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_exam_result_rejects_foreign_class_and_out_of_range_marks(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $mumbai = Campus::create(['school_id'=>$school->id,'name'=>'Mumbai','code'=>'MUM']);
        $pune = Campus::create(['school_id'=>$school->id,'name'=>'Pune','code'=>'PUN']);
        $year = AcademicYear::create(['school_id'=>$school->id,'name'=>'2026-27','starts_on'=>'2026-06-01','ends_on'=>'2027-05-31']);
        $mClass = ClassSection::create(['campus_id'=>$mumbai->id,'academic_year_id'=>$year->id,'name'=>'FYJC A','code'=>'FYJCA']);
        $pClass = ClassSection::create(['campus_id'=>$pune->id,'academic_year_id'=>$year->id,'name'=>'FYJC B','code'=>'FYJCB']);
        $subject = Subject::create(['school_id'=>$school->id,'name'=>'IT','code'=>'IT']);
        $student = Student::create(['school_id'=>$school->id,'campus_id'=>$mumbai->id,'admission_number'=>'EX39','first_name'=>'Student','last_name'=>'One','admission_date'=>'2026-06-01','status'=>'active']);
        $mEnrollment = ClassEnrollment::create(['student_id'=>$student->id,'class_section_id'=>$mClass->id]);
        $pEnrollment = ClassEnrollment::create(['student_id'=>$student->id,'class_section_id'=>$pClass->id]);
        $admin = User::create(['name'=>'Mumbai Admin','email'=>'m39@example.test','password'=>'password123','role'=>'staff']);
        CampusMembership::create(['campus_id'=>$mumbai->id,'user_id'=>$admin->id,'role'=>'campus_admin']);
        $this->actingAs($admin)->post(route('campus.exams.store',[$school,$mumbai]),[
            'class_section_id'=>$mClass->id,'subject_id'=>$subject->id,'name'=>'Unit Test 1',
            'exam_date'=>'2026-11-01','max_marks'=>30,'pass_marks'=>12,
        ])->assertRedirect();
        $exam = Examination::firstOrFail();
        $this->post(route('campus.exams.result',[$school,$mumbai,$exam]),[
            'class_enrollment_id'=>$mEnrollment->id,'status'=>'graded','marks_obtained'=>29,
        ])->assertRedirect();
        $this->assertDatabaseHas('exam_results',['examination_id'=>$exam->id,'marks_obtained'=>29]);
        $this->post(route('campus.exams.result',[$school,$mumbai,$exam]),[
            'class_enrollment_id'=>$pEnrollment->id,'status'=>'graded','marks_obtained'=>25,
        ])->assertNotFound();
        $this->post(route('campus.exams.result',[$school,$mumbai,$exam]),[
            'class_enrollment_id'=>$mEnrollment->id,'status'=>'graded','marks_obtained'=>31,
        ])->assertSessionHasErrors('marks_obtained');
        $this->get(route('campus.exams.index',[$school,$pune]))->assertForbidden();
        $this->assertSame(1,ExamResult::count());
    }
}
