<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase37CampusTeachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_admin_only_assigns_own_campus_teacher_and_class(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $mumbai = Campus::create(['school_id'=>$school->id,'name'=>'Mumbai','code'=>'MUM']);
        $pune = Campus::create(['school_id'=>$school->id,'name'=>'Pune','code'=>'PUN']);
        $year = AcademicYear::create(['school_id'=>$school->id,'name'=>'2026-27','starts_on'=>'2026-06-01','ends_on'=>'2027-05-31']);
        $mClass = ClassSection::create(['campus_id'=>$mumbai->id,'academic_year_id'=>$year->id,'name'=>'FYJC A','code'=>'FYJCA']);
        $pClass = ClassSection::create(['campus_id'=>$pune->id,'academic_year_id'=>$year->id,'name'=>'FYJC B','code'=>'FYJCB']);
        $subject = Subject::create(['school_id'=>$school->id,'name'=>'IT','code'=>'IT']);
        $admin = User::create(['name'=>'Mumbai Admin','email'=>'m37@example.test','password'=>'password123','role'=>'staff']);
        $teacherUser = User::create(['name'=>'Teacher One','email'=>'t37@example.test','password'=>'password123','role'=>'teacher']);
        CampusMembership::create(['campus_id'=>$mumbai->id,'user_id'=>$admin->id,'role'=>'campus_admin']);
        $teacher = TeacherProfile::create(['user_id'=>$teacherUser->id,'school_id'=>$school->id,'campus_id'=>$mumbai->id,'employee_code'=>'E37','designation'=>'Lecturer','joined_on'=>'2026-06-01']);
        $payload = ['teacher_profile_id'=>$teacher->id,'class_section_id'=>$mClass->id,'subject_id'=>$subject->id];
        $this->actingAs($admin)->post(route('campus.teaching.store',[$school,$mumbai]),$payload)->assertRedirect();
        $this->post(route('campus.teaching.store',[$school,$mumbai]),$payload)->assertRedirect();
        $this->assertSame(1,TeacherSubjectAssignment::where($payload)->count());

        $this->post(route('campus.teaching.store',[$school,$mumbai]),[
            ...$payload, 'class_section_id'=>$pClass->id,
        ])->assertNotFound();
        $this->get(route('campus.teaching.index',[$school,$pune]))->assertForbidden();
        $this->assertDatabaseCount('teacher_subject_assignments',1);
    }
}
