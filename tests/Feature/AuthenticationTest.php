<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/students')->assertRedirect('/login');
    }

    public function test_authenticated_student_cannot_access_student_records(): void
    {
        $student = User::create(['name' => 'Demo Student', 'email' => 'demo.student@example.test', 'password' => 'password-for-testing', 'role' => 'student']);
        $this->actingAs($student)->get('/students')->assertForbidden();
    }

    public function test_staff_can_view_students(): void
    {
        $staff = User::create(['name' => 'Demo Staff', 'email' => 'demo.staff@example.test', 'password' => 'password-for-testing', 'role' => 'staff']);
        $this->actingAs($staff)->get('/students')->assertOk();
    }

    public function test_login_regenerates_session_and_logout_ends_session(): void
    {
        User::create(['name' => 'Demo Admin', 'email' => 'demo.admin@example.test', 'password' => 'password-for-testing', 'role' => 'admin']);

        $this->post('/login', ['email' => 'demo.admin@example.test', 'password' => 'password-for-testing'])
            ->assertRedirect('/');
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
