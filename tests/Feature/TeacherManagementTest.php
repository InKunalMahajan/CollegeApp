<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role, string $suffix): User
    {
        return User::create([
            'name' => ucfirst($role).' '.$suffix,
            'email' => $role.$suffix.'@example.test',
            'password' => 'testing-password-123',
            'role' => $role,
        ]);
    }

    private function fixtures(string $suffix): array
    {
        $school = School::create(['name' => 'School '.$suffix, 'code' => $suffix]);
        $campus = Campus::create(['school_id' => $school->id, 'name' => 'Main', 'code' => 'MAIN']);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026-27',
            'starts_on' => '2026-06-01', 'ends_on' => '2027-05-31']);
        $section = ClassSection::create(['campus_id' => $campus->id,
            'academic_year_id' => $year->id, 'name' => 'A', 'code' => 'A']);
        $subject = Subject::create(['school_id' => $school->id,
            'name' => 'English', 'code' => 'ENG']);
        return [$school, $campus, $section, $subject];
    }

    public function test_only_admin_can_manage_teacher_profiles(): void
    {
        $this->get('/teachers')->assertRedirect('/login');
        foreach (['staff', 'student', 'teacher'] as $role) {
            $this->actingAs($this->account($role, $role))
                ->get('/teachers')->assertForbidden();
        }
    }

    public function test_admin_creates_teacher_and_assigns_subject_and_class(): void
    {
        [$school, $campus, $section, $subject] = $this->fixtures('A');
        $teacher = $this->account('teacher', 'alpha');
        $this->actingAs($this->account('admin', 'alpha'));
        $this->post('/teachers', ['user_id' => $teacher->id, 'school_id' => $school->id,
            'campus_id' => $campus->id, 'employee_code' => 'T001'])
            ->assertRedirect(route('teachers.index'));
        $profile = TeacherProfile::where('user_id', $teacher->id)->firstOrFail();

        $this->post('/teacher-assignments/subjects', [
            'teacher_profile_id' => $profile->id, 'subject_id' => $subject->id,
            'class_section_id' => $section->id,
        ])->assertRedirect(route('teacher-assignments.index'));

        $this->post('/teacher-assignments/classes', [
            'teacher_profile_id' => $profile->id, 'class_section_id' => $section->id,
        ])->assertRedirect(route('teacher-assignments.index'));
        $this->assertDatabaseCount('teacher_subject_assignments', 1);
        $this->assertDatabaseCount('class_teacher_assignments', 1);

        $this->actingAs($teacher)->get('/my-teaching')
            ->assertOk()->assertSee('English')->assertSee('My class teacher responsibilities');
    }

    public function test_cross_school_subject_and_cross_campus_class_are_rejected(): void
    {
        [$school, $campus, $section] = $this->fixtures('B');
        [, , $otherSection, $otherSubject] = $this->fixtures('C');
        $teacher = $this->account('teacher', 'cross');
        $profile = TeacherProfile::create(['user_id' => $teacher->id,
            'school_id' => $school->id, 'campus_id' => $campus->id, 'employee_code' => 'T002']);
        $this->actingAs($this->account('admin', 'cross'));
        $this->post('/teacher-assignments/subjects', [
            'teacher_profile_id' => $profile->id, 'subject_id' => $otherSubject->id,
            'class_section_id' => $section->id,
        ])->assertSessionHasErrors('subject_id');
        $this->post('/teacher-assignments/classes', [
            'teacher_profile_id' => $profile->id, 'class_section_id' => $otherSection->id,
        ])->assertSessionHasErrors('class_section_id');
    }

    public function test_only_one_class_teacher_per_section(): void
    {
        [$school, $campus, $section] = $this->fixtures('D');
        $first = $this->account('teacher', 'one');
        $second = $this->account('teacher', 'two');
        $one = TeacherProfile::create(['user_id' => $first->id,
            'school_id' => $school->id, 'campus_id' => $campus->id, 'employee_code' => 'T1']);
        $two = TeacherProfile::create(['user_id' => $second->id,
            'school_id' => $school->id, 'campus_id' => $campus->id, 'employee_code' => 'T2']);
        $this->actingAs($this->account('admin', 'one'));
        $this->post('/teacher-assignments/classes', [
            'teacher_profile_id' => $one->id, 'class_section_id' => $section->id,
        ])->assertRedirect(route('teacher-assignments.index'));
        $this->post('/teacher-assignments/classes', [
            'teacher_profile_id' => $two->id, 'class_section_id' => $section->id,
        ])->assertSessionHasErrors('class_section_id');
    }

    public function test_teacher_cannot_access_other_teacher_profile_or_admin_assignment_screen(): void
    {
        $teacher = $this->account('teacher', 'alone');
        $this->actingAs($teacher)
            ->get('/teacher-assignments')->assertForbidden();
        $this->get('/my-teaching')->assertOk()->assertSee('Teacher profile pending');
    }
}
