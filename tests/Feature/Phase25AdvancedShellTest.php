<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase25AdvancedShellTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Shell',
            'email' => $role.'@phase25.test',
            'password' => 'testing-password-123',
            'role' => $role,
        ]);
    }

    public function test_student_shell_has_accessible_header_sidebar_and_dropdown(): void
    {
        $this->actingAs($this->account('student'))->get('/')
            ->assertOk()
            ->assertSee('id="mobile-nav-toggle"', false)
            ->assertSee('aria-controls="schoolapp-navigation"', false)
            ->assertSee('id="schoolapp-navigation"', false)
            ->assertSee('id="main-content"', false)
            ->assertSee('Skip to main content')
            ->assertSee('Filter navigation menu')
            ->assertSee('Account menu')
            ->assertSee('My assignments')
            ->assertDontSee('User accounts')
            ->assertDontSee('Student login links');
    }

    public function test_teacher_navigation_preserves_teaching_routes(): void
    {
        $this->actingAs($this->account('teacher'))->get('/')
            ->assertOk()
            ->assertSee('My teaching')
            ->assertSee('My timetable')
            ->assertSee('My lesson progress')
            ->assertDontSee('Institution setup');
    }

    public function test_admin_strict_mode_does_not_display_legacy_administration(): void
    {
        config()->set('school_security.legacy_global_routes_enabled', false);
        $this->actingAs($this->account('admin'))->get('/')
            ->assertOk()
            ->assertSee('My Schools')
            ->assertDontSee('Student login links')
            ->assertDontSee('User accounts');
    }

    public function test_school_context_menu_only_shows_permitted_membership_actions(): void
    {
        $school = School::create(['name' => 'Example School', 'code' => 'EX25']);
        $auditor = $this->account('staff');
        SchoolMembership::create([
            'school_id' => $school->id,
            'user_id' => $auditor->id,
            'role' => 'auditor',
        ]);
        $this->actingAs($auditor)
            ->get(route('school-dashboard.index', $school))
            ->assertOk()
            ->assertSee('Example School')
            ->assertSee('School dashboard')
            ->assertSee('School portal')
            ->assertSee('Security report')
            ->assertDontSee('Student accounts')
            ->assertDontSee('School administration');
    }

    public function test_unauthenticated_user_cannot_access_shell(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
