<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase38CampusAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_admin_records_only_verified_student_attendance(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $mumbai = Campus::create(['school_id'=>$school->id,'name'=>'Mumbai','code'=>'MUM']);
        $pune = Campus::create(['school_id'=>$school->id,'name'=>'Pune','code'=>'PUN']);
        $year = AcademicYear::create(['school_id'=>$school->id,'name'=>'2026-27','starts_on'=>'2026-06-01','ends_on'=>'2027-05-31']);
        $mClass = ClassSection::create(['campus_id'=>$mumbai->id,'academic_year_id'=>$year->id,'name'=>'FYJC A','code'=>'FYJCA']);
        $pClass = ClassSection::create(['campus_id'=>$pune->id,'academic_year_id'=>$year->id,'name'=>'FYJC B','code'=>'FYJCB']);
        $student = Student::create(['school_id'=>$school->id,'campus_id'=>$mumbai->id,'admission_number'=>'ATT38','first_name'=>'Student','last_name'=>'One','admission_date'=>'2026-06-01','status'=>'active']);
        $mEnrollment = ClassEnrollment::create(['student_id'=>$student->id,'class_section_id'=>$mClass->id]);
        $pEnrollment = ClassEnrollment::create(['student_id'=>$student->id,'class_section_id'=>$pClass->id]);
        $admin = User::create(['name'=>'Mumbai Admin','email'=>'m38@example.test','password'=>'password123','role'=>'staff']);
        CampusMembership::create(['campus_id'=>$mumbai->id,'user_id'=>$admin->id,'role'=>'campus_admin']);
        $payload = ['class_enrollment_id'=>$mEnrollment->id,'attendance_date'=>'2026-11-01','status'=>'present'];

        $this->actingAs($admin)->post(route('campus.attendance.student',[$school,$mumbai]),$payload)->assertRedirect();
        $this->post(route('campus.attendance.student',[$school,$mumbai]),[...$payload,'status'=>'late'])->assertRedirect();
        $this->assertSame(1,StudentAttendance::where('class_enrollment_id',$mEnrollment->id)->count());
        $this->assertSame('late',StudentAttendance::where('class_enrollment_id',$mEnrollment->id)->first()->status);
        $this->post(route('campus.attendance.student',[$school,$mumbai]),[
            ...$payload,'class_enrollment_id'=>$pEnrollment->id,
        ])->assertNotFound();
        $this->get(route('campus.attendance.index',[$school,$pune]))->assertForbidden();
        $this->assertDatabaseCount('student_attendances',1);
    }
}
