<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\CurriculumLesson;
use App\Models\CurriculumUnit;
use App\Models\Examination;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7AcademicTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $suffix = ''): User
    {
        return User::create([
            'name' => ucfirst($role).' '.$suffix,
            'email' => $role.$suffix.'@phase7.example.test',
            'password' => 'secure-password-123',
            'role' => $role,
        ]);
    }

    private function seedClass(string $suffix): array
    {
        $school = School::create(['name' => 'School '.$suffix, 'code' => $suffix]);
        $campus = Campus::create(['school_id' => $school->id, 'name' => 'Main', 'code' => 'M']);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-27',
            'starts_on' => '2026-06-01', 'ends_on' => '2027-05-31',
        ]);
        $section = ClassSection::create([
            'campus_id' => $campus->id, 'academic_year_id' => $year->id,
            'name' => 'Grade 8', 'code' => 'G8',
        ]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MAT']);
        $student = Student::create([
            'admission_number' => 'ADM'.$suffix, 'first_name' => 'Demo', 'last_name' => 'Learner',
            'admission_date' => '2026-06-01',
        ]);
        $enrollment = ClassEnrollment::create(['student_id' => $student->id, 'class_section_id' => $section->id]);
        $teacherUser = $this->user('teacher', $suffix);
        $teacher = TeacherProfile::create([
            'user_id' => $teacherUser->id, 'school_id' => $school->id,
            'campus_id' => $campus->id, 'employee_code' => 'EMP'.$suffix,
        ]);
        $assignment = TeacherSubjectAssignment::create([
            'teacher_profile_id' => $teacher->id, 'subject_id' => $subject->id,
            'class_section_id' => $section->id,
        ]);
        return compact('school', 'section', 'subject', 'enrollment', 'teacherUser', 'assignment');
    }

    public function test_guest_and_non_admin_cannot_manage_exams_or_curriculum(): void
    {
        $this->get('/exams')->assertRedirect('/login');
        $this->get('/curriculum')->assertRedirect('/login');
        foreach (['staff', 'student', 'teacher'] as $role) {
            $this->actingAs($this->user($role, $role))
                ->get('/exams')->assertForbidden();
            $this->get('/curriculum')->assertForbidden();
        }
    }

    public function test_admin_creates_exam_and_records_marks_and_grade(): void
    {
        $data = $this->seedClass('A');
        $this->actingAs($this->user('admin', 'one'));
        $this->post('/exams', [
            'class_section_id' => $data['section']->id,
            'subject_id' => $data['subject']->id, 'name' => 'Unit Test 1',
            'exam_date' => '2026-10-01', 'max_marks' => 30, 'pass_marks' => 12,
        ])->assertRedirect(route('exams.index'));
        $exam = Examination::firstOrFail();
        $this->post('/exams/'.$exam->id.'/results', [
            'class_enrollment_id' => $data['enrollment']->id,
            'status' => 'graded', 'marks_obtained' => 27,
        ])->assertRedirect(route('exams.results', $exam));
        $result = $exam->results()->firstOrFail();
        $this->assertSame(90.0, $result->percentage);
        $this->assertSame('A+', $result->grade);
        $this->post('/exams/'.$exam->id.'/results', [
            'class_enrollment_id' => $data['enrollment']->id,
            'status' => 'graded', 'marks_obtained' => 31,
        ])->assertSessionHasErrors('marks_obtained');
        $this->assertDatabaseCount('exam_results', 1);
    }

    public function test_cross_school_exam_and_wrong_class_result_are_rejected(): void
    {
        $one = $this->seedClass('B');
        $two = $this->seedClass('C');
        $this->actingAs($this->user('admin', 'two'));
        $this->post('/exams', [
            'class_section_id' => $one['section']->id,
            'subject_id' => $two['subject']->id, 'name' => 'Wrong school',
            'exam_date' => '2026-10-01', 'max_marks' => 50, 'pass_marks' => 20,
        ])->assertSessionHasErrors('subject_id');
        $exam = Examination::create([
            'class_section_id' => $one['section']->id,
            'subject_id' => $one['subject']->id, 'name' => 'Valid exam',
            'exam_date' => '2026-10-01', 'max_marks' => 50, 'pass_marks' => 20,
        ]);
        $this->post('/exams/'.$exam->id.'/results', [
            'class_enrollment_id' => $two['enrollment']->id,
            'status' => 'graded', 'marks_obtained' => 45,
        ])->assertSessionHasErrors('class_enrollment_id');
    }

    public function test_teacher_tracks_only_own_subject_lessons(): void
    {
        $one = $this->seedClass('D');
        $two = $this->seedClass('E');
        $unit = CurriculumUnit::create([
            'subject_id' => $one['subject']->id, 'title' => 'Numbers', 'sequence' => 1,
        ]);
        $lesson = CurriculumLesson::create([
            'curriculum_unit_id' => $unit->id, 'title' => 'Addition',
            'sequence' => 1, 'estimated_minutes' => 45,
        ]);
        $this->actingAs($one['teacherUser'])->get('/my-curriculum')->assertOk()->assertSee('Addition');
        $this->post('/my-curriculum/progress', [
            'teacher_subject_assignment_id' => $one['assignment']->id,
            'curriculum_lesson_id' => $lesson->id, 'status' => 'completed',
            'completed_on' => '2026-10-01',
        ])->assertRedirect(route('curriculum.mine'));
        $this->assertDatabaseHas('lesson_progress', ['status' => 'completed']);
        $this->actingAs($two['teacherUser'])
            ->post('/my-curriculum/progress', [
                'teacher_subject_assignment_id' => $one['assignment']->id,
                'curriculum_lesson_id' => $lesson->id, 'status' => 'completed',
                'completed_on' => '2026-10-01',
            ])->assertForbidden();
        $this->assertDatabaseCount('lesson_progress', 1);
        $this->post('/my-curriculum/progress', [
            'teacher_subject_assignment_id' => $two['assignment']->id,
            'curriculum_lesson_id' => $lesson->id, 'status' => 'completed',
            'completed_on' => '2026-10-01',
        ])->assertSessionHasErrors('curriculum_lesson_id');
    }

    public function test_completed_lesson_requires_date(): void
    {
        $data = $this->seedClass('F');
        $unit = CurriculumUnit::create([
            'subject_id' => $data['subject']->id, 'title' => 'Numbers', 'sequence' => 1,
        ]);
        $lesson = CurriculumLesson::create([
            'curriculum_unit_id' => $unit->id, 'title' => 'Counting', 'sequence' => 1,
            'estimated_minutes' => 30,
        ]);
        $this->actingAs($data['teacherUser'])
            ->post('/my-curriculum/progress', [
                'teacher_subject_assignment_id' => $data['assignment']->id,
                'curriculum_lesson_id' => $lesson->id, 'status' => 'completed',
            ])->assertSessionHasErrors('completed_on');
    }
}
