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

class Phase31EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $code): School
    {
        return School::create(['name' => 'School '.$code, 'code' => 'P31'.$code]);
    }

    private function member(School $school, string $role, string $name): User
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower($name).'@phase31.test',
            'password' => 'strong-password-31',
            'role' => 'staff',
        ]);
        SchoolMembership::create([
            'school_id' => $school->id, 'user_id' => $user->id, 'role' => $role,
        ]);
        return $user;
    }

    private function details(string $number): array
    {
        return [
            'employee_number' => $number,
            'display_name' => 'Employee '.$number,
            'job_title' => 'Teacher',
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'joined_on' => '2026-01-20',
            'work_email' => strtolower($number).'@example.test',
        ];
    }

    public function test_admin_can_create_employee_with_lifecycle_and_view_private_change_history(): void
    {
        $school = $this->school('CREATE');
        $admin = $this->member($school, 'school_admin', 'CreateAdmin');

        $this->actingAs($admin)->post(route('school-employees.store', $school), $this->details('E31A'))
            ->assertRedirect();
        $employee = SchoolEmployee::where('school_id', $school->id)
            ->where('employee_number', 'E31A')->firstOrFail();

        $this->assertSame('full_time', $employee->employment_type);
        $this->assertSame('active', $employee->employment_status);
        $this->assertSame('2026-01-20', $employee->joined_on->toDateString());

        $this->get(route('school-employees.show', [$school, $employee]))
            ->assertOk()->assertSee('Employee change history')->assertSee('E31A');
        $this->assertDatabaseHas('school_audit_events', [
            'school_id' => $school->id,
            'subject_type' => 'school_employee',
            'subject_id' => $employee->id,
            'action' => 'employee.created',
        ]);
    }

    public function test_only_admin_can_change_employment_status_and_audit_does_not_record_email_values(): void
    {
        $school = $this->school('STATUS');
        $admin = $this->member($school, 'school_admin', 'StatusAdmin');
        $auditor = $this->member($school, 'auditor', 'StatusAuditor');
        $employee = SchoolEmployee::create(['school_id' => $school->id, ...$this->details('E31B')]);

        $this->actingAs($auditor)->put(route('school-employees.update', [$school, $employee]), [
            ...$this->details('E31B'), 'employment_status' => 'separated',
        ])->assertForbidden();

        $this->actingAs($admin)->put(route('school-employees.update', [$school, $employee]), [
            ...$this->details('E31B'), 'employment_status' => 'on_leave',
            'work_email' => 'updated31@example.test',
        ])->assertRedirect();

        $this->assertSame('on_leave', $employee->fresh()->employment_status);
        $event = \App\Models\SchoolAuditEvent::where('school_id', $school->id)
            ->where('action', 'employee.updated')
            ->where('subject_id', $employee->id)->firstOrFail();

        $this->assertContains('employment_status', $event->context['fields']);
        $this->assertContains('work_email', $event->context['fields']);
        $this->assertStringNotContainsString('updated31@example.test', json_encode($event->context));

        $this->actingAs($auditor)->get(route('school-employees.show', [$school, $employee]))
            ->assertOk()->assertDontSee('Employee change history');
    }

    public function test_employee_status_type_and_work_email_validation(): void
    {
        $school = $this->school('VALID');
        $admin = $this->member($school, 'school_admin', 'ValidAdmin');
        SchoolEmployee::create(['school_id' => $school->id, ...$this->details('E31C')]);

        $this->actingAs($admin)->post(route('school-employees.store', $school), [
            ...$this->details('E31D'),
            'employment_status' => 'super_admin',
            'employment_type' => 'permanent_forever',
            'joined_on' => '2099-01-01',
            'work_email' => 'e31c@example.test',
        ])->assertSessionHasErrors([
            'employment_status', 'employment_type', 'joined_on', 'work_email',
        ]);

        $this->assertDatabaseMissing('school_employees', [
            'school_id' => $school->id, 'employee_number' => 'E31D',
        ]);
    }

    public function test_school_scoped_email_uniqueness_and_other_school_same_email(): void
    {
        $first = $this->school('A');
        $second = $this->school('B');
        $admin = $this->member($first, 'school_admin', 'EmailAdmin');
        SchoolEmployee::create(['school_id' => $first->id, ...$this->details('E31E')]);

        $this->actingAs($admin)->post(route('school-employees.store', $first), [
            ...$this->details('E31F'), 'work_email' => 'e31e@example.test',
        ])->assertSessionHasErrors('work_email');

        SchoolEmployee::create([
            'school_id' => $second->id, ...$this->details('E31G'),
            'work_email' => 'e31e@example.test',
        ]);
        $this->assertSame(2, SchoolEmployee::where('work_email', 'e31e@example.test')->count());
    }

    public function test_department_members_only_access_scoped_employee_profiles(): void
    {
        $school = $this->school('SCOPE');
        $otherSchool = $this->school('FOREIGN');
        $viewer = $this->member($school, 'department_member', 'ProfileViewer');
        $skills = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Skills', 'code' => 'SK',
        ]);
        $finance = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Finance', 'code' => 'FIN',
        ]);
        SchoolDepartmentMembership::create([
            'school_id' => $school->id, 'school_department_id' => $skills->id,
            'user_id' => $viewer->id, 'role' => 'department_viewer',
        ]);
        $staff = SchoolEmployee::create([
            'school_id' => $school->id, ...$this->details('E31H'),
            'school_department_id' => $skills->id,
        ]);
        $head = SchoolEmployee::create([
            'school_id' => $school->id, ...$this->details('E31I'),
            'school_department_id' => $finance->id,
        ]);
        $staff->update(['manager_id' => $head->id]);
        $foreign = SchoolEmployee::create([
            'school_id' => $otherSchool->id, ...$this->details('E31J'),
        ]);

        $this->actingAs($viewer)->get(route('school-employees.show', [$school, $staff]))
            ->assertOk()->assertSee('E31H')->assertDontSee('Employee E31I');
        $this->get(route('school-employees.show', [$school, $head]))->assertForbidden();
        $this->get(route('school-employees.show', [$school, $foreign]))->assertNotFound();
        $this->get(route('school-employees.show', [$otherSchool, $foreign]))->assertForbidden();
    }

    public function test_filtering_and_legacy_employees_default_to_active(): void
    {
        $school = $this->school('FILTER');
        $admin = $this->member($school, 'school_admin', 'FilterAdmin');
        SchoolEmployee::create([
            'school_id' => $school->id,
            'employee_number' => 'OLD31',
            'display_name' => 'Existing Employee',
            'job_title' => 'Teacher',
        ]);
        SchoolEmployee::create([
            'school_id' => $school->id, ...$this->details('E31K'),
            'employment_status' => 'separated',
        ]);

        $this->actingAs($admin)->get(route('school-employees.index', [
            $school, 'employment_status' => 'active',
        ]))->assertOk()->assertSee('Existing Employee')
            ->assertDontSee('Employee E31K');

        $this->get(route('school-employees.index', [
            $school, 'employment_status' => 'separated',
        ]))->assertOk()->assertSee('Employee E31K')
            ->assertDontSee('Existing Employee');
    }
}
