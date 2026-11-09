<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolEmployee;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Phase28EmployeeOrgTreeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $suffix): User
    {
        return User::create([
            'name' => 'Employee '.$suffix,
            'email' => strtolower($suffix).'@phase28.test',
            'password' => 'valid-test-password-123',
            'role' => 'staff',
        ]);
    }

    private function member(User $user, School $school, string $role): void
    {
        SchoolMembership::create(['school_id' => $school->id, 'user_id' => $user->id, 'role' => $role]);
    }

    private function employeeData(string $number): array
    {
        return [
            'employee_number' => $number,
            'display_name' => 'Example '.$number,
            'job_title' => 'Assistant Lecturer',
            'department_label' => 'Campus Operations > Academic Foundations',
            'location' => 'Pune',
        ];
    }

    public function test_only_school_admin_can_add_employee(): void
    {
        $a = School::create(['name' => 'Alpha', 'code' => 'ALPHA28']);
        $b = School::create(['name' => 'Beta', 'code' => 'BETA28']);
        $admin = $this->user('Admin');
        $auditor = $this->user('Auditor');
        $this->member($admin, $a, 'school_admin');
        $this->member($auditor, $a, 'auditor');

        $this->post(route('school-employees.store', $a), $this->employeeData('T1001'))
            ->assertRedirect('/login');
        $this->actingAs($auditor)
            ->post(route('school-employees.store', $a), $this->employeeData('T1001'))
            ->assertForbidden();
        $this->actingAs($admin)
            ->post(route('school-employees.store', $b), $this->employeeData('T1001'))
            ->assertForbidden();
        $this->post(route('school-employees.store', $a), $this->employeeData('T1001'))
            ->assertRedirect();

        $this->assertDatabaseHas('school_employees', [
            'school_id' => $a->id, 'employee_number' => 'T1001',
        ]);
        $this->assertDatabaseHas('school_audit_events', [
            'school_id' => $a->id, 'action' => 'employee.created',
        ]);
    }

    public function test_manager_must_belong_to_same_school_and_cycles_are_rejected(): void
    {
        $a = School::create(['name' => 'Alpha', 'code' => 'ALPHA29']);
        $b = School::create(['name' => 'Beta', 'code' => 'BETA29']);
        $admin = $this->user('Manager');
        $this->member($admin, $a, 'school_admin');

        $head = SchoolEmployee::create([
            'school_id' => $a->id, ...$this->employeeData('T2001'),
        ]);
        $assistant = SchoolEmployee::create([
            'school_id' => $a->id, ...$this->employeeData('T2002'),
            'manager_id' => $head->id,
        ]);
        $foreign = SchoolEmployee::create([
            'school_id' => $b->id, ...$this->employeeData('T2003'),
        ]);

        $this->actingAs($admin)
            ->post(route('school-employees.store', $a), [
                ...$this->employeeData('T2004'), 'manager_id' => $foreign->id,
            ])->assertSessionHasErrors('manager_id');

        $this->put(route('school-employees.update', [$a, $head]), [
            ...$this->employeeData('T2001'), 'manager_id' => $assistant->id,
        ])->assertSessionHasErrors('manager_id');

        $this->put(route('school-employees.update', [$b, $foreign]), [
            ...$this->employeeData('T2003'),
        ])->assertNotFound();

        $this->assertNull($head->fresh()->manager_id);
    }

    public function test_auditor_can_read_without_admin_forms_and_unrelated_user_is_denied(): void
    {
        $school = School::create(['name' => 'Gamma', 'code' => 'GAMMA28']);
        $auditor = $this->user('ReadOnly');
        $outsider = $this->user('Outsider');
        $this->member($auditor, $school, 'auditor');
        SchoolEmployee::create(['school_id' => $school->id, ...$this->employeeData('T3001')]);

        $this->actingAs($auditor)->get(route('school-employees.index', $school))
            ->assertOk()->assertSee('Example T3001')
            ->assertDontSee('Add new employee')->assertDontSee('Import OrgTree CSV');

        $this->actingAs($outsider)
            ->get(route('school-employees.index', $school))->assertForbidden();
    }

    public function test_department_members_only_see_explicitly_assigned_department_employees(): void
    {
        $school = School::create(['name' => 'Delta', 'code' => 'DELTA28']);
        $department = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Skills', 'code' => 'SKILLS',
        ]);
        $reader = $this->user('Skills');
        $this->member($reader, $school, 'department_member');
        SchoolDepartmentMembership::create([
            'school_id' => $school->id,
            'school_department_id' => $department->id,
            'user_id' => $reader->id,
            'role' => 'department_viewer',
        ]);
        SchoolEmployee::create([
            'school_id' => $school->id, ...$this->employeeData('T4001'),
            'school_department_id' => $department->id,
        ]);
        SchoolEmployee::create([
            'school_id' => $school->id, ...$this->employeeData('T4002'),
        ]);

        $this->actingAs($reader)->get(route('school-employees.index', $school))
            ->assertOk()->assertSee('Example T4001')
            ->assertDontSee('Example T4002')
            ->assertDontSee('Add new employee');

        $this->post(route('school-employees.store', $school), $this->employeeData('T4003'))
            ->assertForbidden();
    }

    public function test_only_admin_imports_orgtree_csv_without_inventing_managers(): void
    {
        $school = School::create(['name' => 'Epsilon', 'code' => 'EPS28']);
        $admin = $this->user('ImportAdmin');
        $viewer = $this->user('ImportViewer');
        $this->member($admin, $school, 'school_admin');
        $this->member($viewer, $school, 'auditor');

        $csv = "\xEF\xBB\xBFEmployee Number,Display Name,Job Title,Department,Location,Reportees Count\n"
            ."T5001,Executive One,Director,Campus Operations,Mumbai,5\n"
            ."T5002,Teacher Two,Assistant Lecturer,Campus Operations > Academic Foundations,Pune,0\n";

        $this->actingAs($viewer)->post(route('school-employees.import', $school), [
            'employee_csv' => UploadedFile::fake()->createWithContent('OrgTree.csv', $csv),
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('school-employees.import', $school), [
            'employee_csv' => UploadedFile::fake()->createWithContent('OrgTree.csv', $csv),
        ])->assertRedirect();

        $this->assertSame(2, SchoolEmployee::where('school_id', $school->id)->count());
        $this->assertSame(0, SchoolEmployee::where('school_id', $school->id)->whereNotNull('manager_id')->count());
        $this->assertDatabaseHas('school_employees', [
            'school_id' => $school->id, 'employee_number' => 'T5001', 'location' => 'Mumbai',
        ]);
    }
}
