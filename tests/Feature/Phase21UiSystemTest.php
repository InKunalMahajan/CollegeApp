<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class Phase21UiSystemTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name'=>ucfirst($role).' UI',
            'email'=>$role.'@phase21.test',
            'role'=>$role,
            'password'=>'test-password-123',
        ]);
    }

    public function test_mobile_navigation_and_skip_link_render_for_authenticated_users(): void
    {
        $this->actingAs($this->user('student'))->get('/')
            ->assertOk()
            ->assertSee('Skip to main content')
            ->assertSee('id="mobile-nav-toggle"',false)
            ->assertSee('aria-controls="schoolapp-navigation"',false)
            ->assertSee('aria-expanded="false"',false)
            ->assertSee('id="main-content"',false)
            ->assertSee('My assignments')
            ->assertDontSee('User accounts');
    }

    public function test_school_navigation_does_not_expose_legacy_admin_links_to_students(): void
    {
        $this->actingAs($this->user('teacher'))->get('/')
            ->assertOk()->assertSee('My teaching')
            ->assertDontSee('Student login links')
            ->assertDontSee('User accounts');
    }

    public function test_shared_components_render_accessible_markup(): void
    {
        $html=Blade::render(<<<'BLADE'
<x-ui.card title="School profile" description="Current school">
    <x-ui.badge tone="success">Active</x-ui.badge>
    <x-ui.button type="submit"><x-ui.icon name="save"/> Save</x-ui.button>
    <x-ui.alert type="warning">Check details</x-ui.alert>
    <x-ui.input name="student_name" label="Student name" required />
    <x-ui.table label="Test students"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>Demo</td></tr></tbody></x-ui.table>
</x-ui.card>
BLADE);
        $this->assertStringContainsString('School profile',$html);
        $this->assertStringContainsString('aria-label="Test students"',$html);
        $this->assertStringContainsString('for="student_name"',$html);
        $this->assertStringContainsString('type="submit"',$html);
        $this->assertStringContainsString('role="status"',$html);
        $this->assertStringContainsString('aria-hidden="true"',$html);
    }

    public function test_login_page_still_renders_csrf_form_and_feedback(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('Email address')
            ->assertSee('Password')
            ->assertSee('Sign in')
            ->assertSee('type="submit"',false);
    }
}
