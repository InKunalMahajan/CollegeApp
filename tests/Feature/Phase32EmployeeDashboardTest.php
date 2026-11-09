<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolEmployee;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase32EmployeeDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $suffix): School
    {
        return School::create(['name' => 'School '.$suffix, 'code' => 'P32'.$suffix]);
    }

    private function user(string $suffix, School $school, string $role): User
    {
        $user = User::create([
            'name' => 'Test '.$suffix,
            'email' => strtolower($suffix).'@phase32.test',
            'password' => 'phase32-test-password',
            'role' => 'staff',
        ]);

        SchoolMembership::create([
            'user_id' => $user->id, 'school_id' => $school->id, 'role' => $role,
        ]);

        return $user;
    }

    private function employee(School $school, string $number, array $extra = []): SchoolEmployee
    {
        return SchoolEmployee::create([
            'school_id' => $school->id,
            'employee_number' => $number,
            'display_name' => 'Employee '.$number,
            'job_title' => 'Lecturer',
            'employment_status' => 'active',
            ...$extra,
        ]);
    }

    public function test_dashboard_kpis_are_scoped_before_filters_and_do_not_leak_other_schools(): void
    {
        $alpha = $this->school('ALPHA');
        $beta = $this->school('BETA');
        $auditor = $this->user('Auditor', $alpha, 'auditor');
        $this->employee($alpha, '001');
        $this->employee($alpha, '002', ['employment_status' => 'on_leave']);
        $this->employee($beta, 'SECRET', ['employment_status' => 'separated']);

        $this->actingAs($auditor)->get(route('school-employees.index', $alpha))
            ->assertOk()
            ->assertSee('Total employees visible')
            ->assertSee('Active employees')
            ->assertSee('Employee 001')
            ->assertSee('Employee 002')
            ->assertDontSee('Employee SECRET')
            ->assertDontSee('Add new employee');

        $this->get(route('school-employees.index', $beta))->assertForbidden();
    }

    public function test_combined_filters_find_only_matching_records(): void
    {
        $school = $this->school('FILTER');
        $auditor = $this->user('Filter', $school, 'auditor');
        $skills = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Skills', 'code' => 'SK',
        ]);
        $finance = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Finance', 'code' => 'FIN',
        ]);
        $this->employee($school, 'SEARCH01', [
            'display_name' => 'Anjali Mentor',
            'job_title' => 'Senior Trainer',
            'school_department_id' => $skills->id,
            'location' => 'Pune',
            'employment_status' => 'active',
        ]);
        $this->employee($school, 'SEARCH02', [
            'display_name' => 'Separate Colleague',
            'job_title' => 'Trainer',
            'school_department_id' => $finance->id,
            'location' => 'Mumbai',
        ]);
        $this->employee($school, 'SEARCH03', [
            'display_name' => 'Retired Mentor',
            'job_title' => 'Senior Trainer',
            'school_department_id' => $skills->id,
            'location' => 'Pune',
            'employment_status' => 'separated',
        ]);

        $this->actingAs($auditor)->get(route('school-employees.index', [
            $school,
            'search' => 'Senior Trainer',
            'employment_status' => 'active',
            'school_department_id' => $skills->id,
            'location' => 'Pune',
        ]))->assertOk()
            ->assertSee('Anjali Mentor')
            ->assertSee('1 matching employee')
            ->assertDontSee('Separate Colleague')
            ->assertDontSee('Retired Mentor');

        $this->get(route('school-employees.index', [
            $school, 'search' => 'SEARCH02',
        ]))->assertOk()->assertSee('Separate Colleague')->assertDontSee('Anjali Mentor');
    }

    public function test_department_member_counts_search_and_locations_only_include_assigned_department(): void
    {
        $school = $this->school('SCOPE');
        $viewer = $this->user('Viewer', $school, 'department_member');
        $skills = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Skills', 'code' => 'SK',
        ]);
        $finance = SchoolDepartment::create([
            'school_id' => $school->id, 'name' => 'Private Finance', 'code' => 'FIN',
        ]);
        SchoolDepartmentMembership::create([
            'school_id' => $school->id,
            'school_department_id' => $skills->id,
            'user_id' => $viewer->id,
            'role' => 'department_viewer',
        ]);
        $this->employee($school, 'VISIBLE32', [
            'display_name' => 'Visible Staff',
            'school_department_id' => $skills->id,
            'location' => 'Pune',
        ]);
        $this->employee($school, 'PRIVATE32', [
            'display_name' => 'Private Staff',
            'school_department_id' => $finance->id,
            'location' => 'Hidden City',
        ]);

        $this->actingAs($viewer)->get(route('school-employees.index', $school))
            ->assertOk()->assertSee('Visible Staff')
            ->assertSee('1 matching employee')
            ->assertDontSee('Private Staff')
            ->assertDontSee('Private Finance')
            ->assertDontSee('Hidden City');

        $this->get(route('school-employees.index', [
            $school, 'search' => 'Private Staff',
        ]))->assertOk()
            ->assertSee('0 matching employees')
            ->assertDontSee('Private Staff');
    }

    public function test_invalid_status_and_foreign_department_filters_are_rejected(): void
    {
        $school = $this->school('VALID');
        $another = $this->school('OTHER');
        $auditor = $this->user('Validate', $school, 'auditor');
        $outside = SchoolDepartment::create([
            'school_id' => $another->id, 'name' => 'Outside', 'code' => 'OUT',
        ]);

        $this->actingAs($auditor)->get(route('school-employees.index', [
            $school, 'employment_status' => 'super_admin',
        ]))->assertSessionHasErrors('employment_status');

        $this->get(route('school-employees.index', [
            $school, 'school_department_id' => $outside->id,
        ]))->assertSessionHasErrors('school_department_id');
    }

    public function test_paginated_directory_retains_search_filters(): void
    {
        $school = $this->school('PAGES');
        $auditor = $this->user('Pages', $school, 'auditor');
        $records = [];
        for ($i = 1; $i <= 24; $i++) {
            $records[] = [
                'school_id' => $school->id,
                'employee_number' => sprintf('PAG%03d', $i),
                'display_name' => sprintf('Paged Lecturer %03d', $i),
                'job_title' => 'Lecturer',
                'employment_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('school_employees')->insert($records);

        $this->actingAs($auditor)->get(route('school-employees.index', [
            $school, 'search' => 'Paged Lecturer', 'page' => 2,
        ]))->assertOk()
            ->assertSee('Paged Lecturer 024')
            ->assertDontSee('Paged Lecturer 001')
            ->assertSee('search=Paged%20Lecturer', false);
    }

    public function test_large_roster_hides_recursive_tree_but_keeps_directory_available(): void
    {
        $school = $this->school('LARGE');
        $auditor = $this->user('Large', $school, 'auditor');
        $records = [];
        for ($i = 1; $i <= 251; $i++) {
            $records[] = [
                'school_id' => $school->id,
                'employee_number' => sprintf('L%04d', $i),
                'display_name' => sprintf('Staff %04d', $i),
                'job_title' => 'Lecturer',
                'employment_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('school_employees')->insert($records);

        $this->actingAs($auditor)->get(route('school-employees.index', $school))
            ->assertOk()
            ->assertSee('The tree is hidden for more than 250 matching employees')
            ->assertSee('Staff 0001')
            ->assertSee('251 matching employees');
    }
}
