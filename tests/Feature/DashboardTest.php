<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_staff_sees_dashboard_and_student_actions(): void
    {
        $staff = User::create([
            'name' => 'Staff Example',
            'email' => 'staff.dashboard@example.test',
            'password' => 'testing-password',
            'role' => 'staff',
        ]);

        $this->actingAs($staff)
            ->get('/')
            ->assertOk()
            ->assertSee('Recent students')
            ->assertSee('Add student');
    }

    public function test_student_sees_restricted_dashboard_without_student_directory(): void
    {
        $student = User::create([
            'name' => 'Student Example',
            'email' => 'student.dashboard@example.test',
            'password' => 'testing-password',
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->get('/')
            ->assertOk()
            ->assertSee('Your workspace')
            ->assertDontSee('Recent students')
            ->assertDontSee('Add student');
    }
}
