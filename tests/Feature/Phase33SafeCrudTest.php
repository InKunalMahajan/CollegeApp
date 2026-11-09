<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolEmployee;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase33SafeCrudTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $code): School
    {
        return School::create(['name' => 'School '.$code, 'code' => 'P33'.$code]);
    }

    private function user(School $school, string $role, string $name): User
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower($name).'@phase33.test',
            'password' => 'safe-phase33-password',
            'role' => 'staff',
        ]);
        SchoolMembership::create([
            'school_id' => $school->id, 'user_id' => $user->id, 'role' => $role,
        ]);
        return $user;
    }

    private function employee(School $school, string $number, array $extra = []): SchoolEmployee
    {
        return SchoolEmployee::create([
            'school_id' => $school->id,
            'employee_number' => $number,
            'display_name' => 'Employee '.$number,
            'job_title' => 'Teacher',
            ...$extra,
        ]);
    }

    public function test_school_admin_can_archive_and_restore_employee_with_audit(): void
    {
        $school = $this->school('ARCH');
        $admin = $this->user($school, 'school_admin', 'ArchiveAdmin');
        $employee = $this->employee($school, 'ARCH33');

        $this->actingAs($admin)
            ->delete(route('school-employees.destroy', [$school, $employee]))
            ->assertRedirect();

        $this->assertSoftDeleted('school_employees', ['id' => $employee->id]);
        $this->assertNull(SchoolEmployee::find($employee->id));
        $this->assertDatabaseHas('school_audit_events', [
            'school_id' => $school->id,
            'subject_type' => 'school_employee',
            'subject_id' => $employee->id,
            'action' => 'employee.archived',
        ]);

        $this->get(route('school-employees.index', $school))
            ->assertOk()->assertSee('Restore employee')->assertSee('ARCH33');

        $this->post(route('school-employees.restore', [$school, $employee->id]))
            ->assertRedirect();
        $this->assertNotNull(SchoolEmployee::find($employee->id));
        $this->assertDatabaseHas('school_audit_events', [
            'school_id' => $school->id,
            'subject_id' => $employee->id,
            'action' => 'employee.restored',
        ]);
    }

    public function test_reportees_must_be_reassigned_before_manager_is_archived(): void
    {
        $school = $this->school('REPORT');
        $admin = $this->user($school, 'school_admin', 'ReportAdmin');
        $manager = $this->employee($school, 'MANAGER33');
        $this->employee($school, 'REPORT33', ['manager_id' => $manager->id]);

        $this->actingAs($admin)
            ->delete(route('school-employees.destroy', [$school, $manager]))
            ->assertSessionHasErrors('employee');

        $this->assertNotNull(SchoolEmployee::find($manager->id));
    }

    public function test_auditor_and_other_school_cannot_archive_or_restore(): void
    {
        $alpha = $this->school('ALPHA');
        $beta = $this->school('BETA');
        $auditor = $this->user($alpha, 'auditor', 'Audit33');
        $admin = $this->user($alpha, 'school_admin', 'SchoolAdmin33');
        $employee = $this->employee($alpha, 'PRIVATE33');

        $this->actingAs($auditor)
            ->delete(route('school-employees.destroy', [$alpha, $employee]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('school-employees.destroy', [$beta, $employee]))
            ->assertForbidden();

        $this->delete(route('school-employees.destroy', [$alpha, $employee]))
            ->assertRedirect();

        $this->actingAs($auditor)
            ->post(route('school-employees.restore', [$alpha, $employee->id]))
            ->assertForbidden();
        $this->actingAs($admin)
            ->post(route('school-employees.restore', [$beta, $employee->id]))
            ->assertForbidden();
    }

    public function test_department_admin_can_update_name_and_code_with_audit(): void
    {
        $school = $this->school('EDIT');
        $admin = $this->user($school, 'school_admin', 'Edit33');
        $department = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Skills', 'code' => 'SKILLS',
        ]);

        $this->actingAs($admin)->put(
            route('school-departments.update', [$school, $department]),
            ['name' => 'Digital Skills', 'code' => 'ds', 'description' => 'Practical training']
        )->assertRedirect();

        $this->assertSame('DS', $department->fresh()->code);
        $this->assertSame('Digital Skills', $department->fresh()->name);
        $this->assertDatabaseHas('school_audit_events', [
            'subject_type' => 'school_department',
            'subject_id' => $department->id,
            'action' => 'department.updated',
        ]);
    }

    public function test_empty_department_can_be_deleted_but_referenced_department_cannot(): void
    {
        $school = $this->school('DELETE');
        $admin = $this->user($school, 'school_admin', 'Delete33');
        $empty = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Empty', 'code' => 'EMPTY',
        ]);
        $used = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Used', 'code' => 'USED',
        ]);
        $this->employee($school, 'STAFF33', ['school_department_id' => $used->id]);

        $this->actingAs($admin)->delete(route('school-departments.destroy', [$school, $used]))
            ->assertSessionHasErrors('department');
        $this->delete(route('school-departments.destroy', [$school, $empty]))
            ->assertRedirect();

        $this->assertDatabaseMissing('school_departments', ['id' => $empty->id]);
        $this->assertDatabaseHas('school_departments', ['id' => $used->id]);
    }

    public function test_department_membership_and_archived_employee_prevent_department_delete(): void
    {
        $school = $this->school('REF');
        $admin = $this->user($school, 'school_admin', 'Refs33');
        $member = $this->user($school, 'department_member', 'Member33');
        $department = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Support', 'code' => 'SUP',
        ]);
        SchoolDepartmentMembership::create([
            'school_id' => $school->id, 'school_department_id' => $department->id,
            'user_id' => $member->id, 'role' => 'department_viewer',
        ]);

        $this->actingAs($admin)
            ->delete(route('school-departments.destroy', [$school, $department]))
            ->assertSessionHasErrors('department');

        $staff = $this->employee($school, 'OLD33', ['school_department_id' => $department->id]);
        $staff->delete();
        SchoolDepartmentMembership::where('school_department_id', $department->id)->delete();

        $this->delete(route('school-departments.destroy', [$school, $department]))
            ->assertSessionHasErrors('department');
    }
}
