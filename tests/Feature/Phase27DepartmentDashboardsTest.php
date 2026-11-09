<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase27DepartmentDashboardsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, string $role = 'staff'): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).'@phase27.test',
            'password' => 'valid-password-123',
            'role' => $role,
        ]);
    }

    private function school(string $code): School
    {
        return School::create(['name' => 'School '.$code, 'code' => $code]);
    }

    private function join(User $user, School $school, string $role = 'school_admin'): void
    {
        SchoolMembership::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'role' => $role,
        ]);
    }

    public function test_department_creation_is_school_admin_only(): void
    {
        $a = $this->school('A');
        $b = $this->school('B');
        $admin = $this->user('Admin', 'admin');
        $outsider = $this->user('Outsider');
        $this->join($admin, $a);

        $this->actingAs($outsider)
            ->post(route('school-departments.store', $a), ['name' => 'Skills', 'code' => 'SKILLS'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('school-departments.store', $b), ['name' => 'Skills', 'code' => 'SKILLS'])
            ->assertForbidden();

        $this->post(route('school-departments.store', $a), [
            'name' => 'Skills', 'code' => 'SKILLS',
        ])->assertRedirect();

        $this->assertDatabaseHas('school_departments', [
            'school_id' => $a->id, 'code' => 'SKILLS',
        ]);
    }

    public function test_department_member_can_only_see_assigned_department_and_school(): void
    {
        $a = $this->school('C');
        $b = $this->school('D');
        $admin = $this->user('Manager', 'admin');
        $staff = $this->user('Employee');
        $this->join($admin, $a);
        $skills = SchoolDepartment::create(['school_id' => $a->id, 'name' => 'Skills', 'code' => 'SK']);
        $finance = SchoolDepartment::create(['school_id' => $a->id, 'name' => 'Finance', 'code' => 'FI']);
        $foreign = SchoolDepartment::create(['school_id' => $b->id, 'name' => 'Other', 'code' => 'OT']);

        $this->actingAs($admin)->post(route('school-departments.assign', [$a, $skills]), [
            'email' => $staff->email, 'role' => 'department_staff',
        ])->assertRedirect();

        $this->assertDatabaseHas('school_memberships', [
            'school_id' => $a->id, 'user_id' => $staff->id, 'role' => 'department_member',
        ]);

        $this->actingAs($staff)
            ->get(route('school-departments.show', [$a, $skills]))->assertOk()
            ->assertSee('Department staff')
            ->assertDontSee('Save role access');

        $this->get(route('school-departments.show', [$a, $finance]))->assertForbidden();
        $this->get(route('school-departments.show', [$b, $foreign]))->assertForbidden();
        $this->get(route('school-departments.show', [$a, $foreign]))->assertNotFound();
        $this->get(route('school-dashboard.index', $a))->assertForbidden();
        $this->get(route('school-security.report', $a))->assertForbidden();
        $this->get(route('school-departments.index', $a))->assertOk()
            ->assertSee('Skills')->assertDontSee('Finance');
    }

    public function test_head_viewer_and_admin_permissions_are_separate(): void
    {
        $school = $this->school('E');
        $admin = $this->user('Director', 'admin');
        $viewer = $this->user('Observer');
        $this->join($admin, $school);
        $department = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Academic', 'code' => 'ACA',
        ]);

        $this->actingAs($admin)->post(route('school-departments.assign', [$school, $department]), [
            'email' => $viewer->email, 'role' => 'department_viewer',
        ])->assertRedirect();

        $this->actingAs($viewer)
            ->get(route('school-departments.show', [$school, $department]))->assertOk()
            ->assertDontSee('Save role access');

        $this->post(route('school-departments.assign', [$school, $department]), [
            'email' => $viewer->email, 'role' => 'department_head',
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('school-departments.assign', [$school, $department]), [
            'email' => $viewer->email, 'role' => 'department_head',
        ])->assertRedirect();

        $this->assertDatabaseHas('school_department_memberships', [
            'school_department_id' => $department->id, 'user_id' => $viewer->id, 'role' => 'department_head',
        ]);
    }

    public function test_revoking_last_department_access_removes_only_department_school_membership(): void
    {
        $school = $this->school('F');
        $admin = $this->user('Principal', 'admin');
        $member = $this->user('Tutor');
        $this->join($admin, $school);
        $department = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Skills', 'code' => 'SKILLS',
        ]);
        $this->actingAs($admin)->post(route('school-departments.assign', [$school, $department]), [
            'email' => $member->email, 'role' => 'department_viewer',
        ]);
        $assignment = SchoolDepartmentMembership::where('user_id', $member->id)->firstOrFail();
        $this->delete(route('school-departments.revoke', [$school, $department, $assignment]))
            ->assertRedirect();

        $this->assertDatabaseMissing('school_department_memberships', ['id' => $assignment->id]);
        $this->assertDatabaseMissing('school_memberships', [
            'school_id' => $school->id, 'user_id' => $member->id,
        ]);
        $this->actingAs($member)
            ->get(route('school-departments.show', [$school, $department]))->assertForbidden();
    }

    public function test_school_auditor_is_not_automatically_department_member(): void
    {
        $school = $this->school('G');
        $auditor = $this->user('Audit');
        $this->join($auditor, $school, 'auditor');
        $department = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Finance', 'code' => 'FIN',
        ]);
        $this->actingAs($auditor)
            ->get(route('school-departments.show', [$school, $department]))->assertForbidden();
    }
}
