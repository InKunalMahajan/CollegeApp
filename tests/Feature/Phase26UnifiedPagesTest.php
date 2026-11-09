<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase26UnifiedPagesTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Layout',
            'email' => $role.'-layout@phase26.test',
            'password' => 'strong-test-password',
            'role' => $role,
        ]);
    }

    public function test_student_dashboard_renders_the_scoped_content_system(): void
    {
        $this->actingAs($this->person('student'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="schoolapp-page', false)
            ->assertSee('id="main-content"', false)
            ->assertSee('Your workspace')
            ->assertDontSee('User accounts');
    }

    public function test_staff_dashboard_keeps_student_actions_with_unified_cards(): void
    {
        $this->actingAs($this->person('staff'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recent students')
            ->assertSee('Add student')
            ->assertSee('schoolapp-panel');
    }

    public function test_design_styles_remain_scoped_to_app_pages(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('.schoolapp-page', $css);
        $this->assertStringContainsString('.schoolapp-panel', $css);
        $this->assertStringContainsString('table tbody td', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('@media print', $css);
    }
}
