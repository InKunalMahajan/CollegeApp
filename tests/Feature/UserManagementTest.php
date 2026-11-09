<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Example',
            'email' => $role.'@example.test',
            'password' => 'example-password-123',
            'role' => $role,
        ]);
    }

    public function test_guest_cannot_view_users(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_all_four_roles_can_sign_in(): void
    {
        foreach (['admin', 'staff', 'teacher', 'student'] as $role) {
            $this->makeUser($role);
            $this->post('/login', [
                'email' => $role.'@example.test',
                'password' => 'example-password-123',
            ])->assertRedirect('/');
            $this->assertAuthenticated();
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_only_administrators_can_manage_user_accounts(): void
    {
        foreach (['staff', 'teacher', 'student'] as $role) {
            $this->actingAs($this->makeUser($role))->get('/users')->assertForbidden();
            $this->post('/users', [
                'name' => 'Unauthorized', 'email' => 'unauthorized@example.test',
                'role' => 'admin', 'password' => 'example-password-123',
                'password_confirmation' => 'example-password-123',
            ])->assertForbidden();
        }
    }

    public function test_explicit_website_owner_can_create_a_teacher_account(): void
    {
        $admin = $this->makeUser('admin');
        WebsiteOwner::create(['user_id' => $admin->id]);
        $this->actingAs($admin)
            ->post('/users', [
                'name' => 'New Teacher',
                'email' => 'new.teacher@example.test',
                'role' => 'teacher',
                'password' => 'very-long-password',
                'password_confirmation' => 'very-long-password',
            ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.teacher@example.test',
            'role' => 'teacher',
        ]);
        $this->assertNotEquals(
            'very-long-password',
            User::where('email', 'new.teacher@example.test')->firstOrFail()->password
        );
    }

    public function test_website_owner_cannot_create_account_with_short_password(): void
    {
        $admin = $this->makeUser('admin');
        WebsiteOwner::create(['user_id' => $admin->id]);
        $this->actingAs($admin)
            ->post('/users', [
                'name' => 'Short Password',
                'email' => 'short@example.test',
                'role' => 'student',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'short@example.test']);
    }
}
