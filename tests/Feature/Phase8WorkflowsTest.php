<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\SchoolAssignment;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase8WorkflowsTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role, string $suffix): User {
        return User::create(['name'=>ucfirst($role).' '.$suffix,'email'=>$role.$suffix.'@phase8.test',
            'role'=>$role,'password'=>'secure-pass-123']);
    }

    private function fixtures(string $code): array {
        $school=School::create(['name'=>'School '.$code,'code'=>$code]);
        $campus=Campus::create(['school_id'=>$school->id,'name'=>'Campus','code'=>'C']);
        $year=AcademicYear::create(['school_id'=>$school->id,'name'=>'2026-27','starts_on'=>'2026-06-01','ends_on'=>'2027-05-31']);
        $section=ClassSection::create(['campus_id'=>$campus->id,'academic_year_id'=>$year->id,'name'=>'A','code'=>'A']);
        $subject=Subject::create(['school_id'=>$school->id,'name'=>'Math','code'=>'MAT']);
        $teacher=$this->account('teacher',$code);
        $profile=TeacherProfile::create(['user_id'=>$teacher->id,'school_id'=>$school->id,'campus_id'=>$campus->id,'employee_code'=>'T'.$code]);
        $allocation=TeacherSubjectAssignment::create(['teacher_profile_id'=>$profile->id,'class_section_id'=>$section->id,'subject_id'=>$subject->id]);
        $student=$this->account('student',$code);
        $record=Student::create(['admission_number'=>'A'.$code,'first_name'=>'Learner','last_name'=>$code,'admission_date'=>'2026-06-01']);
        StudentAccount::create(['user_id'=>$student->id,'student_id'=>$record->id]);
        $enrollment=ClassEnrollment::create(['student_id'=>$record->id,'class_section_id'=>$section->id]);
        return compact('allocation','teacher','student','enrollment');
    }

    public function test_teacher_assigns_student_submits_and_teacher_grades(): void {
        $f=$this->fixtures('A');
        $this->actingAs($f['teacher'])->post('/my-assignments',[
            'teacher_subject_assignment_id'=>$f['allocation']->id,'title'=>'Practice',
            'instructions'=>'Solve the question','due_at'=>now()->addDays(3)->format('Y-m-d H:i:s'),
            'max_marks'=>20,
        ])->assertRedirect(route('assignments.teacher'));
        $assignment=SchoolAssignment::firstOrFail();
        $this->assertSame('draft',$assignment->status);
        $this->actingAs($f['student'])->get('/my-assignments')->assertDontSee('Practice');
        $this->actingAs($f['teacher'])->post('/my-assignments/'.$assignment->id.'/publish')
            ->assertRedirect(route('assignments.teacher'));
        $this->actingAs($f['student'])->get('/my-assignments')->assertSee('Practice');
        $this->post('/my-assignments/'.$assignment->id.'/submit',['response'=>'Answer 42'])
            ->assertRedirect(route('assignments.student'));
        $submission=AssignmentSubmission::firstOrFail();
        $this->actingAs($f['teacher'])->post('/my-assignments/submissions/'.$submission->id.'/grade',[
            'marks'=>18,'feedback'=>'Good work',
        ])->assertRedirect(route('assignments.submissions',$assignment));
        $this->actingAs($f['student'])->get('/my-assignments')->assertSee('18 / 20');
        $this->post('/my-assignments/'.$assignment->id.'/submit',['response'=>'Change'])
            ->assertSessionHasErrors('response');
    }

    public function test_cross_student_and_cross_teacher_access_is_denied(): void {
        $a=$this->fixtures('B');$b=$this->fixtures('C');
        $assignment=SchoolAssignment::create(['teacher_subject_assignment_id'=>$a['allocation']->id,
            'title'=>'Private homework','instructions'=>'Work','due_at'=>now()->addDay(),'max_marks'=>50,'status'=>'published']);
        $this->actingAs($b['student'])->get('/my-assignments')->assertDontSee('Private homework');
        $this->post('/my-assignments/'.$assignment->id.'/submit',['response'=>'Unauthorized'])->assertNotFound();
        $this->actingAs($b['teacher'])->get('/my-assignments/'.$assignment->id.'/submissions')->assertForbidden();
        $this->post('/my-assignments/'.$assignment->id.'/publish')->assertForbidden();
    }

    public function test_announcements_are_draft_until_published_and_role_filtered(): void {
        $admin=$this->account('admin','D');$teacher=$this->account('teacher','D');
        $this->actingAs($admin)->post('/announcements',[
            'title'=>'For teachers','body'=>'Staff discussion','audience'=>'teacher',
        ])->assertRedirect(route('announcements.index'));
        $draft=\App\Models\SchoolAnnouncement::firstOrFail();
        $this->actingAs($teacher)->get('/announcements')->assertDontSee('Staff discussion');
        $this->post('/announcements/'.$draft->id.'/publish')->assertForbidden();
        $this->actingAs($admin)->post('/announcements/'.$draft->id.'/publish')
            ->assertRedirect(route('announcements.index'));
        $this->actingAs($teacher)->get('/announcements')->assertSee('Staff discussion');
        $this->actingAs($admin)->get('/announcements')->assertDontSee('Staff discussion');
    }

    public function test_student_cannot_publish_or_grade_and_teacher_cannot_link_students(): void {
        $f=$this->fixtures('E');
        $this->actingAs($f['student'])->post('/my-assignments',[])->assertForbidden();
        $this->actingAs($f['teacher'])->get('/student-links')->assertForbidden();
        $this->actingAs($f['student'])->post('/announcements',[])->assertForbidden();
    }
}
