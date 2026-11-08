<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_has_all_sixteen_unique_modules(): void
    {
        $modules = config('college_modules');
        $this->assertCount(16, $modules);
        $this->assertCount(16, array_unique(array_column($modules, 'key')));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/modules')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_catalog(): void
    {
        foreach (['student', 'teacher', 'staff'] as $role) {
            $user = User::create([
                'name' => ucfirst($role),
                'email' => $role.'-modules@example.test',
                'password' => 'demo-password-123',
                'role' => $role,
            ]);
            $this->actingAs($user)->get('/modules')->assertForbidden();
        }
    }

    public function test_admin_can_see_module_catalog_and_planned_labels(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-modules@example.test',
            'password' => 'demo-password-123',
            'role' => 'admin',
        ]);
        $this->actingAs($admin)->get('/modules')
            ->assertOk()
            ->assertSee('Library Management')
            ->assertSee('Attendance Management')
            ->assertSee('Planned');
    }
}
