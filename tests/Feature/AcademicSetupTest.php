<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicSetupTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $role.'-phase4@example.test',
            'password' => 'test-password-123',
            'role' => $role,
        ]);
    }

    public function test_guest_is_redirected_and_non_admin_cannot_manage_school(): void
    {
        $this->get('/setup/schools')->assertRedirect('/login');
        foreach (['student', 'teacher', 'staff'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get('/setup/schools')->assertForbidden();
        }
    }

    public function test_admin_can_create_school_campus_year_subject_and_class(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $this->post('/setup/schools', ['name' => 'College One', 'code' => 'C1'])
            ->assertRedirect('/setup/schools');
        $school = School::where('code', 'C1')->firstOrFail();

        $this->post('/setup/campuses', [
            'school_id' => $school->id, 'name' => 'Main', 'code' => 'MAIN',
        ])->assertRedirect('/setup/campuses');
        $campus = Campus::where('code', 'MAIN')->firstOrFail();

        $this->post('/setup/academic-years', [
            'school_id' => $school->id, 'name' => '2026-27',
            'starts_on' => '2026-06-01', 'ends_on' => '2027-05-31',
        ])->assertRedirect('/setup/academic-years');
        $year = AcademicYear::where('name', '2026-27')->firstOrFail();

        $this->post('/setup/subjects', [
            'school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH',
        ])->assertRedirect('/setup/subjects');
        $this->post('/setup/class-sections', [
            'campus_id' => $campus->id, 'academic_year_id' => $year->id,
            'name' => 'FYJC A', 'code' => 'FYJC-A',
        ])->assertRedirect('/setup/class-sections');
        $this->assertDatabaseHas('class_sections', ['name' => 'FYJC A']);
    }

    public function test_class_cannot_reference_year_from_another_school(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $one = School::create(['name' => 'One', 'code' => 'S1']);
        $two = School::create(['name' => 'Two', 'code' => 'S2']);
        $campus = Campus::create(['school_id' => $one->id, 'name' => 'Main', 'code' => 'M']);
        $year = AcademicYear::create([
            'school_id' => $two->id, 'name' => '2026-27',
            'starts_on' => '2026-06-01', 'ends_on' => '2027-05-31',
        ]);
        $this->post('/setup/class-sections', [
            'campus_id' => $campus->id, 'academic_year_id' => $year->id,
            'name' => 'FYJC A', 'code' => 'A',
        ])->assertSessionHasErrors('academic_year_id');
        $this->assertDatabaseCount('class_sections', 0);
    }

    public function test_admin_can_edit_school_and_duplicate_code_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $one = School::create(['name' => 'One', 'code' => 'ONE']);
        School::create(['name' => 'Two', 'code' => 'TWO']);
        $this->put('/setup/schools/'.$one->id, ['name' => 'Updated', 'code' => 'ONE'])
            ->assertRedirect('/setup/schools');
        $this->post('/setup/schools', ['name' => 'Duplicate', 'code' => 'ONE'])
            ->assertSessionHasErrors('code');
    }
}
