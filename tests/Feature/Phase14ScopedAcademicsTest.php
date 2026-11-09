<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase14ScopedAcademicsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $code): User
    {
        return User::create([
            'name' => $role.' '.$code,
            'email' => $role.$code.'@phase14.test',
            'role' => $role,
            'password' => 'secure-password-123',
        ]);
    }

    private function fixtures(string $code): array
    {
        $school = School::create(['name' => 'School '.$code, 'code' => $code]);
        $campus = Campus::create(['school_id' => $school->id, 'name' => 'Main', 'code' => 'MAIN']);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-27',
            'starts_on' => '2026-06-01', 'ends_on' => '2027-05-31',
        ]);
        $section = ClassSection::create([
            'campus_id' => $campus->id, 'academic_year_id' => $year->id,
            'name' => 'FYJC '.$code, 'code' => 'FYJC',
        ]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'IT '.$code, 'code' => 'IT']);
        $student = Student::create(['school_id'=>$school->id,
            'admission_number' => 'ADM'.$code,
            'first_name' => 'Student'.$code, 'last_name' => 'Sample', 'admission_date' => '2026-06-01',
        ]);
        $enrollment = ClassEnrollment::create([
            'student_id' => $student->id, 'class_section_id' => $section->id,
        ]);
        $teacherUser = $this->user('teacher', $code);
        $teacher = TeacherProfile::create([
            'user_id' => $teacherUser->id, 'school_id' => $school->id,
            'campus_id' => $campus->id, 'employee_code' => 'EMP'.$code,
        ]);
        return compact('school','section','subject','student','enrollment','teacher');
    }

    private function member(User $user, School $school, string $role = 'school_admin'): void
    {
        SchoolMembership::create([
            'school_id' => $school->id, 'user_id' => $user->id, 'role' => $role,
        ]);
    }

    public function test_membership_required_and_school_a_cannot_see_school_b(): void
    {
        $a=$this->fixtures('A');
        $b=$this->fixtures('B');
        $admin=$this->user('admin','one');
        $url=route('school-academics.index', $a['school']);

        $this->get($url)->assertRedirect('/login');
        $this->actingAs($admin)->get($url)->assertForbidden();
        $this->member($admin,$a['school']);
        $this->get($url)->assertOk()
            ->assertSee('StudentA')->assertSee('IT A')
            ->assertDontSee('StudentB')->assertDontSee('IT B');
        $this->get(route('school-academics.index',$b['school']))->assertForbidden();
    }

    public function test_foreign_school_ids_cannot_be_used_for_writes(): void
    {
        $a=$this->fixtures('C');
        $b=$this->fixtures('D');
        $admin=$this->user('admin','two');
        $this->member($admin,$a['school']);
        $this->actingAs($admin);
        $base=[$a['school']];

        $this->post(route('school-academics.attendance',$base),[
            'class_enrollment_id'=>$b['enrollment']->id,
            'attendance_date'=>'2026-10-01','status'=>'present',
        ])->assertNotFound();

        $this->post(route('school-academics.teaching',$base),[
            'teacher_profile_id'=>$b['teacher']->id,
            'class_section_id'=>$a['section']->id,
            'subject_id'=>$a['subject']->id,
        ])->assertNotFound();

        $this->post(route('school-academics.exams',$base),[
            'class_section_id'=>$a['section']->id,
            'subject_id'=>$b['subject']->id,
            'name'=>'Cross school exam','exam_date'=>'2026-10-01',
            'max_marks'=>50,'pass_marks'=>20,
        ])->assertNotFound();

        $exam=Examination::create([
            'class_section_id'=>$b['section']->id,'subject_id'=>$b['subject']->id,
            'name'=>'Other Exam','exam_date'=>'2026-10-01','max_marks'=>50,'pass_marks'=>20,
        ]);
        $this->post(route('school-academics.results',[$a['school'],$exam]),[
            'class_enrollment_id'=>$a['enrollment']->id,
            'status'=>'graded','marks_obtained'=>25,
        ])->assertNotFound();

        $this->assertDatabaseCount('student_attendances',0);
        $this->assertDatabaseCount('teacher_subject_assignments',0);
        $this->assertDatabaseCount('exam_results',0);
    }

    public function test_school_a_attendance_updates_one_day_and_creates_audit(): void
    {
        $a=$this->fixtures('E');
        $admin=$this->user('admin','three');
        $this->member($admin,$a['school']);
        $this->actingAs($admin);
        $input=[
            'class_enrollment_id'=>$a['enrollment']->id,
            'attendance_date'=>'2026-10-01','status'=>'present',
        ];
        $this->post(route('school-academics.attendance',$a['school']),$input)
            ->assertRedirect(route('school-academics.index',$a['school']));
        $this->post(route('school-academics.attendance',$a['school']),
            array_merge($input,['status'=>'late']))
            ->assertRedirect(route('school-academics.index',$a['school']));

        $this->assertDatabaseCount('student_attendances',1);
        $this->assertSame('late',StudentAttendance::firstOrFail()->status);
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$a['school']->id,'action'=>'academics.attendance.saved',
        ]);
    }

    public function test_teacher_assignment_timetable_exam_and_result_are_scoped(): void
    {
        $a=$this->fixtures('F');
        $admin=$this->user('admin','four');
        $this->member($admin,$a['school']);
        $this->actingAs($admin);
        $school=$a['school'];

        $this->post(route('school-academics.teaching',$school),[
            'teacher_profile_id'=>$a['teacher']->id,
            'subject_id'=>$a['subject']->id,
            'class_section_id'=>$a['section']->id,
        ])->assertRedirect(route('school-academics.index',$school));
        $assignment=TeacherSubjectAssignment::firstOrFail();

        $this->post(route('school-academics.timetable',$school),[
            'teacher_subject_assignment_id'=>$assignment->id,
            'day_of_week'=>1,'starts_at'=>'09:00','ends_at'=>'10:00',
        ])->assertRedirect(route('school-academics.index',$school));
        $this->assertDatabaseCount('timetable_entries',1);

        $this->post(route('school-academics.exams',$school),[
            'class_section_id'=>$a['section']->id,
            'subject_id'=>$a['subject']->id,
            'name'=>'Unit Test','exam_date'=>'2026-10-01',
            'max_marks'=>50,'pass_marks'=>20,
        ])->assertRedirect(route('school-academics.index',$school));
        $exam=Examination::firstOrFail();

        $this->post(route('school-academics.results',[$school,$exam]),[
            'class_enrollment_id'=>$a['enrollment']->id,
            'status'=>'graded','marks_obtained'=>42,
        ])->assertRedirect(route('school-academics.index',$school));
        $this->assertSame(42,ExamResult::firstOrFail()->marks_obtained);
        $this->assertDatabaseHas('school_audit_events',[
            'school_id'=>$school->id,'action'=>'academics.result.saved',
        ]);
    }
}
