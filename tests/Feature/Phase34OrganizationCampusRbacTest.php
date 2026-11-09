<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\School;
use App\Models\SchoolEmployee;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase34OrganizationCampusRbacTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $code = 'TEACH'): School
    {
        return School::create(['name' => $code, 'code' => 'P34'.$code]);
    }

    private function campus(School $school, string $name): Campus
    {
        return Campus::create([
            'school_id' => $school->id,
            'name' => 'TEACH '.$name,
            'code' => strtoupper($name),
            'city' => $name,
        ]);
    }

    private function user(string $name, string $globalRole = 'staff'): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).'@phase34.test',
            'password' => 'phase34-safe-password',
            'role' => $globalRole,
        ]);
    }

    private function organizationAdmin(School $school): User
    {
        $user = $this->user('HeadOffice');
        SchoolMembership::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'role' => 'school_admin',
        ]);

        return $user;
    }

    private function campusAdmin(Campus $campus, string $name = 'MumbaiAdmin'): User
    {
        $user = $this->user($name);
        CampusMembership::create([
            'campus_id' => $campus->id,
            'user_id' => $user->id,
            'role' => 'campus_admin',
        ]);

        return $user;
    }

    private function employee(School $school, ?Campus $campus, string $number): SchoolEmployee
    {
        return SchoolEmployee::create([
            'school_id' => $school->id,
            'campus_id' => $campus?->id,
            'employee_number' => $number,
            'display_name' => 'Employee '.$number,
            'job_title' => 'Teacher',
            'employment_status' => 'active',
        ]);
    }

    private function student(School $school, ?Campus $campus, string $number): Student
    {
        return Student::create([
            'school_id' => $school->id,
            'campus_id' => $campus?->id,
            'admission_number' => $number,
            'first_name' => 'Student',
            'last_name' => $number,
            'admission_date' => '2026-06-01',
            'status' => 'active',
        ]);
    }

    public function test_organization_admin_creates_campuses_and_explicitly_grants_mumbai_only_admin(): void
    {
        $school = $this->school();
        $headOffice = $this->organizationAdmin($school);
        $user = $this->user('NewMumbai');

        $this->actingAs($headOffice)
            ->post(route('school-campuses.store', $school), [
                'name' => 'TEACH Mumbai', 'code' => 'mumbai', 'city' => 'Mumbai',
            ])->assertRedirect();

        $mumbai = Campus::where('school_id', $school->id)->firstOrFail();
        $this->assertSame('MUMBAI', $mumbai->code);
        $pune = $this->campus($school, 'Pune');

        $this->post(route('school-campuses.grant', [$school, $mumbai]), [
            'email' => $user->email,
        ])->assertRedirect();

        $this->assertDatabaseHas('campus_memberships', [
            'campus_id' => $mumbai->id, 'user_id' => $user->id, 'role' => 'campus_admin',
        ]);
        $this->assertDatabaseMissing('school_memberships', [
            'school_id' => $school->id, 'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('campus-workspace.index', [$school, $mumbai]))
            ->assertOk()->assertSee('TEACH Mumbai');
        $this->get(route('campus-workspace.index', [$school, $pune]))->assertForbidden();
        $this->get(route('school-campuses.index', $school))->assertForbidden();
        $this->get(route('school-students.index', $school))->assertForbidden();
        $this->get(route('school-employees.index', $school))->assertForbidden();

        $this->get(route('my-schools.index'))
            ->assertOk()->assertSee('My campuses')->assertSee('TEACH Mumbai');
    }

    public function test_campus_admin_creates_records_only_for_own_campus_even_if_request_spoofs_ids(): void
    {
        $school = $this->school('SCOPE');
        $mumbai = $this->campus($school, 'Mumbai');
        $pune = $this->campus($school, 'Pune');
        $user = $this->campusAdmin($mumbai);

        $this->actingAs($user)->post(route('campus.employees.store', [$school, $mumbai]), [
            'employee_number' => 'MU34',
            'display_name' => 'Mumbai Lecturer',
            'job_title' => 'Trainer',
            'employment_status' => 'active',
            'school_id' => 999999,
            'campus_id' => $pune->id,
        ])->assertRedirect();

        $employee = SchoolEmployee::where('employee_number', 'MU34')->firstOrFail();
        $this->assertSame($school->id, $employee->school_id);
        $this->assertSame($mumbai->id, $employee->campus_id);

        $this->post(route('campus.students.store', [$school, $mumbai]), [
            'admission_number' => 'MU34S',
            'first_name' => 'Mumbai',
            'last_name' => 'Student',
            'admission_date' => '2026-06-01',
            'status' => 'active',
            'school_id' => 999999,
            'campus_id' => $pune->id,
        ])->assertRedirect();

        $student = Student::where('admission_number', 'MU34S')->firstOrFail();
        $this->assertSame($school->id, $student->school_id);
        $this->assertSame($mumbai->id, $student->campus_id);
        $this->assertDatabaseHas('school_audit_events', [
            'school_id' => $school->id,
            'action' => 'campus.employee.created',
            'subject_id' => $employee->id,
        ]);
    }

    public function test_mumbai_admin_cannot_view_update_or_archive_pune_records(): void
    {
        $school = $this->school('CROSS');
        $mumbai = $this->campus($school, 'Mumbai');
        $pune = $this->campus($school, 'Pune');
        $admin = $this->campusAdmin($mumbai);
        $puneEmployee = $this->employee($school, $pune, 'PUNE34');
        $puneStudent = $this->student($school, $pune, 'PUNES34');
        $mumbaiEmployee = $this->employee($school, $mumbai, 'MUM34');

        $this->actingAs($admin)->get(route('campus-workspace.index', [$school, $mumbai]))
            ->assertOk()
            ->assertSee('Employee MUM34')
            ->assertDontSee('Employee PUNE34')
            ->assertDontSee('PUNES34');

        $this->put(route('campus.employees.update', [$school, $mumbai, $puneEmployee]), [
            'employee_number' => 'PUNE34',
            'display_name' => 'Stolen',
            'job_title' => 'Teacher',
            'employment_status' => 'active',
        ])->assertNotFound();

        $this->delete(route('campus.employees.archive', [$school, $mumbai, $puneEmployee]))
            ->assertNotFound();

        $this->put(route('campus.students.update', [$school, $mumbai, $puneStudent]), [
            'admission_number' => 'PUNES34',
            'first_name' => 'Stolen',
            'last_name' => 'Record',
            'admission_date' => '2026-06-01',
            'status' => 'inactive',
        ])->assertNotFound();

        $this->delete(route('campus.students.deactivate', [$school, $mumbai, $puneStudent]))
            ->assertNotFound();

        $this->assertSame('Employee PUNE34', $puneEmployee->fresh()->display_name);
        $this->assertSame('active', $puneStudent->fresh()->status);
    }

    public function test_organization_admin_can_manage_all_own_campuses_but_not_another_organization(): void
    {
        $school = $this->school('ONE');
        $other = $this->school('TWO');
        $mumbai = $this->campus($school, 'Mumbai');
        $pune = $this->campus($school, 'Pune');
        $delhi = $this->campus($other, 'Delhi');
        $headOffice = $this->organizationAdmin($school);

        $this->actingAs($headOffice)->get(route('campus-workspace.index', [$school, $mumbai]))
            ->assertOk();
        $this->get(route('campus-workspace.index', [$school, $pune]))->assertOk();
        $this->get(route('campus-workspace.index', [$other, $delhi]))->assertForbidden();
        $this->get(route('campus-workspace.index', [$school, $delhi]))->assertNotFound();
        $this->get(route('school-campuses.index', $other))->assertForbidden();
    }

    public function test_one_campus_admin_assignment_per_organization_and_revoke_removes_access(): void
    {
        $school = $this->school('GRANT');
        $mumbai = $this->campus($school, 'Mumbai');
        $pune = $this->campus($school, 'Pune');
        $headOffice = $this->organizationAdmin($school);
        $campusAdmin = $this->campusAdmin($mumbai, 'AssignedMumbai');

        $this->actingAs($headOffice)->post(route('school-campuses.grant', [$school, $pune]), [
            'email' => $campusAdmin->email,
        ])->assertSessionHasErrors('email');

        $membership = CampusMembership::where('campus_id', $mumbai->id)->firstOrFail();
        $this->delete(route('school-campuses.revoke', [$school, $mumbai, $membership]))
            ->assertRedirect();

        $this->actingAs($campusAdmin)
            ->get(route('campus-workspace.index', [$school, $mumbai]))->assertForbidden();
    }

    public function test_organization_admin_explicitly_assigns_only_own_school_unassigned_records(): void
    {
        $school = $this->school('ASSIGN');
        $other = $this->school('FOREIGN');
        $mumbai = $this->campus($school, 'Mumbai');
        $foreign = $this->campus($other, 'Delhi');
        $headOffice = $this->organizationAdmin($school);
        $employee = $this->employee($school, null, 'UNASS34');
        $student = $this->student($school, null, 'UNASS34S');

        $this->actingAs($headOffice)
            ->post(route('school-campuses.assign-employee', [$school, $mumbai, $employee]))
            ->assertRedirect();
        $this->post(route('school-campuses.assign-student', [$school, $mumbai, $student]))
            ->assertRedirect();

        $this->assertSame($mumbai->id, $employee->fresh()->campus_id);
        $this->assertSame($mumbai->id, $student->fresh()->campus_id);
        $this->post(route('school-campuses.assign-employee', [$school, $foreign, $employee]))
            ->assertNotFound();
        $this->post(route('school-campuses.assign-student', [$school, $foreign, $student]))
            ->assertNotFound();
    }

    public function test_campus_admin_can_archive_and_restore_own_employee_but_student_delete_is_deactivation(): void
    {
        $school = $this->school('LIFE');
        $mumbai = $this->campus($school, 'Mumbai');
        $admin = $this->campusAdmin($mumbai);
        $employee = $this->employee($school, $mumbai, 'LIFE34');
        $student = $this->student($school, $mumbai, 'LIFES34');

        $this->actingAs($admin)
            ->delete(route('campus.employees.archive', [$school, $mumbai, $employee]))
            ->assertRedirect();
        $this->assertSoftDeleted('school_employees', ['id' => $employee->id]);

        $this->post(route('campus.employees.restore', [$school, $mumbai, $employee->id]))
            ->assertRedirect();
        $this->assertNotNull($employee->fresh());

        $this->delete(route('campus.students.deactivate', [$school, $mumbai, $student]))
            ->assertRedirect();
        $this->assertDatabaseHas('students', [
            'id' => $student->id, 'status' => 'inactive',
            'school_id' => $school->id, 'campus_id' => $mumbai->id,
        ]);
    }

    public function test_raw_database_rejects_cross_organization_campus_assignments(): void
    {
        $school = $this->school('INTEGRITY');
        $other = $this->school('UNRELATED');
        $foreignCampus = $this->campus($other, 'Delhi');
        $employee = $this->employee($school, null, 'INTEGRITY34');
        $student = $this->student($school, null, 'INTEGRITY34S');

        foreach ([
            ['school_employees', $employee->id],
            ['students', $student->id],
        ] as [$table, $id]) {
            try {
                DB::table($table)->where('id', $id)
                    ->update(['campus_id' => $foreignCampus->id]);
                $this->fail('Database permitted linking a record to a campus in another organization.');
            } catch (QueryException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }

        $this->assertNull($employee->fresh()->campus_id);
        $this->assertNull($student->fresh()->campus_id);
    }

    public function test_global_admin_without_a_membership_cannot_access_any_campus(): void
    {
        $school = $this->school('GLOBAL');
        $campus = $this->campus($school, 'Mumbai');
        $user = $this->user('GlobalOnly', 'admin');

        $this->actingAs($user)
            ->get(route('campus-workspace.index', [$school, $campus]))->assertForbidden();
        $this->get(route('school-campuses.index', $school))->assertForbidden();
    }
}
