<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolEmployee;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Services\SchoolAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase30CentralizedRbacTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $key, string $globalRole = 'staff'): User
    {
        return User::create([
            'name' => $key.' Example',
            'email' => strtolower($key).'@phase30.test',
            'password' => 'secure-phase30-password',
            'role' => $globalRole,
        ]);
    }

    private function school(string $key): School
    {
        return School::create(['name' => $key.' Academy', 'code' => 'P30'.$key]);
    }

    private function assignSchool(User $person, School $school, string $role): void
    {
        SchoolMembership::create([
            'school_id' => $school->id,
            'user_id' => $person->id,
            'role' => $role,
        ]);
    }

    private function addDepartment(User $person, School $school, string $role): SchoolDepartment
    {
        $department = SchoolDepartment::create([
            'school_id' => $school->id,
            'name' => 'Skills',
            'code' => 'SKILLS',
        ]);
        SchoolDepartmentMembership::create([
            'school_id' => $school->id,
            'school_department_id' => $department->id,
            'user_id' => $person->id,
            'role' => $role,
        ]);
        return $department;
    }

    public function test_permissions_default_deny_unknown_abilities_and_global_admin_cannot_bypass_school_membership(): void
    {
        $school = $this->school('DENY');
        $global = $this->person('Global', 'admin');
        $access = app(SchoolAccess::class);

        $this->assertFalse($access->can($global, $school, 'school.employees.manage'));
        $this->actingAs($global)->get(route('school-permissions.index', $school))->assertForbidden();

        $this->assignSchool($global, $school, 'school_admin');
        $this->assertFalse($access->can($global, $school, 'unknown.permission'));
        $this->assertTrue($access->can($global, $school, 'school.employees.manage'));
        $this->assertTrue($access->can($global, $school, 'school.administration.manage'));
    }

    public function test_school_administrator_matrix_and_write_access_do_not_cross_schools(): void
    {
        $alpha = $this->school('A');
        $beta = $this->school('B');
        $admin = $this->person('Manager');
        $this->assignSchool($admin, $alpha, 'school_admin');

        $this->actingAs($admin)
            ->get(route('school-permissions.index', $alpha))
            ->assertOk()->assertSee('School-level permissions')
            ->assertSee('Department-level permissions');

        $this->get(route('school-permissions.index', $beta))->assertForbidden();
        $this->post(route('school-departments.store', $beta), [
            'name' => 'Finance',
            'code' => 'FINANCE',
        ])->assertForbidden();
        $this->post(route('school-departments.store', $alpha), [
            'name' => 'Finance',
            'code' => 'FINANCE',
        ])->assertRedirect();
    }

    public function test_auditor_can_read_school_matrix_and_employee_tree_but_cannot_mutate_them(): void
    {
        $school = $this->school('AUD');
        $auditor = $this->person('Auditor', 'teacher');
        $this->assignSchool($auditor, $school, 'auditor');

        $this->actingAs($auditor)
            ->get(route('school-permissions.index', $school))->assertOk();
        $this->get(route('school-employees.index', $school))->assertOk();
        $this->get(route('school-dashboard.index', $school))->assertOk();
        $this->get(route('school-departments.index', $school))->assertForbidden();
        $this->post(route('school-employees.store', $school), [
            'employee_number' => 'RBAC30',
            'display_name' => 'Sample',
            'job_title' => 'Teacher',
        ])->assertForbidden();

        $this->post(route('school-employees.import', $school), [])
            ->assertForbidden();
    }

    public function test_department_roles_are_scoped_to_assignments_not_school_wide_abilities(): void
    {
        $school = $this->school('DEP');
        $otherSchool = $this->school('OTHER');
        $head = $this->person('Head', 'admin');
        $this->assignSchool($head, $school, 'department_member');
        $assigned = $this->addDepartment($head, $school, 'department_head');
        $foreign = SchoolDepartment::create([
            'school_id' => $otherSchool->id, 'name' => 'Finance', 'code' => 'FIN',
        ]);
        $access = app(SchoolAccess::class);

        $this->assertTrue($access->canDepartment($head, $school, $assigned, 'department.dashboard.view'));
        $this->assertFalse($access->canDepartment($head, $school, $foreign, 'department.dashboard.view'));
        $this->assertFalse($access->canDepartment($head, $school, $assigned, 'department.roles.manage'));
        $this->assertFalse($access->can($head, $school, 'school.students.view'));
        $this->assertFalse($access->can($head, $school, 'school.finance.manage'));

        $this->actingAs($head)->get(route('school-departments.show', [$school, $assigned]))->assertOk();
        $this->get(route('school-departments.show', [$otherSchool, $foreign]))->assertForbidden();
        $this->get(route('school-departments.show', [$school, $foreign]))->assertNotFound();
        $this->get(route('school-students.index', $school))->assertForbidden();
        $this->get(route('school-finance.fees', $school))->assertForbidden();
        $this->get(route('school-permissions.index', $school))->assertForbidden();

        $this->post(route('school-departments.assign', [$school, $assigned]), [
            'email' => $head->email, 'role' => 'department_viewer',
        ])->assertForbidden();
    }

    public function test_orphan_department_membership_and_unknown_department_role_do_not_grant_access(): void
    {
        $school = $this->school('ORPH');
        $user = $this->person('Orphan');
        $this->assignSchool($user, $school, 'department_member');
        $access = app(SchoolAccess::class);

        $this->assertFalse($access->can($user, $school, 'school.departments.view'));
        $this->actingAs($user)->get(route('school-departments.index', $school))->assertForbidden();
        $this->get(route('school-employees.index', $school))->assertForbidden();

        $department = $this->addDepartment($user, $school, 'department_head');
        $this->assertTrue($access->can($user, $school, 'school.departments.view'));
        SchoolDepartmentMembership::where('school_department_id', $department->id)
            ->where('user_id', $user->id)->update(['role' => 'unexpected_role']);

        $this->assertFalse($access->can($user, $school, 'school.departments.view'));
        $this->get(route('school-departments.show', [$school, $department]))->assertForbidden();
    }

    public function test_existing_teacher_student_global_roles_do_not_gain_school_resources(): void
    {
        $school = $this->school('GLOBAL');
        foreach (['teacher', 'student', 'admin'] as $globalRole) {
            $user = $this->person('Role'.$globalRole, $globalRole);
            $this->actingAs($user)
                ->get(route('school-employees.index', $school))->assertForbidden();
            $this->get(route('school-security.report', $school))->assertForbidden();
        }
    }
}
