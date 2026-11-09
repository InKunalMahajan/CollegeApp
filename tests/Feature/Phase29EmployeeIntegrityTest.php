<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolEmployee;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase29EmployeeIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(string $suffix): array
    {
        $school = School::create([
            'name' => 'School '.$suffix,
            'code' => 'PH29'.$suffix,
        ]);
        $department = SchoolDepartment::create([
            'school_id' => $school->id,
            'code' => 'DEPT',
            'name' => 'Department '.$suffix,
        ]);
        $manager = SchoolEmployee::create([
            'school_id' => $school->id,
            'employee_number' => 'MANAGER'.$suffix,
            'display_name' => 'Manager '.$suffix,
            'job_title' => 'Manager',
            'school_department_id' => $department->id,
        ]);

        return [$school, $department, $manager];
    }

    private function row(int $schoolId, string $number, ?int $managerId, ?int $departmentId): array
    {
        return [
            'school_id' => $schoolId,
            'employee_number' => $number,
            'display_name' => 'Test employee',
            'job_title' => 'Teacher',
            'manager_id' => $managerId,
            'school_department_id' => $departmentId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function test_raw_database_insert_rejects_manager_and_department_from_another_school(): void
    {
        [$schoolA, $departmentA, $managerA] = $this->makeSchool('A');
        [, $departmentB, $managerB] = $this->makeSchool('B');

        foreach ([
            $this->row($schoolA->id, 'FOREIGNMANAGER', $managerB->id, $departmentA->id),
            $this->row($schoolA->id, 'FOREIGNDEPT', $managerA->id, $departmentB->id),
        ] as $data) {
            try {
                DB::table('school_employees')->insert($data);
                $this->fail('The database accepted a cross-school employee relationship.');
            } catch (QueryException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }

        $this->assertDatabaseMissing('school_employees', ['employee_number' => 'FOREIGNMANAGER']);
        $this->assertDatabaseMissing('school_employees', ['employee_number' => 'FOREIGNDEPT']);
    }

    public function test_raw_database_update_rejects_cross_school_relationships(): void
    {
        [$schoolA, $departmentA, $managerA] = $this->makeSchool('C');
        [, $departmentB, $managerB] = $this->makeSchool('D');
        $staff = SchoolEmployee::create($this->row($schoolA->id, 'STAFF29', $managerA->id, $departmentA->id));

        foreach ([
            ['manager_id' => $managerB->id],
            ['school_department_id' => $departmentB->id],
            ['school_id' => $managerB->school_id],
        ] as $change) {
            try {
                DB::table('school_employees')->where('id', $staff->id)->update($change);
                $this->fail('The database accepted a cross-school employee update.');
            } catch (QueryException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }

        $this->assertSame($schoolA->id, (int) $staff->fresh()->school_id);
        $this->assertSame($managerA->id, (int) $staff->fresh()->manager_id);
        $this->assertSame($departmentA->id, (int) $staff->fresh()->school_department_id);
    }

    public function test_parent_school_movement_is_blocked_when_dependents_exist(): void
    {
        [$schoolA, $departmentA, $managerA] = $this->makeSchool('E');
        [$schoolB] = $this->makeSchool('F');
        SchoolEmployee::create($this->row($schoolA->id, 'REPORT29', $managerA->id, $departmentA->id));

        foreach ([
            ['school_employees', $managerA->id],
            ['school_departments', $departmentA->id],
        ] as [$table, $id]) {
            try {
                DB::table($table)->where('id', $id)->update(['school_id' => $schoolB->id]);
                $this->fail('The database allowed moving a referenced row to another school.');
            } catch (QueryException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    public function test_raw_database_self_reporting_is_blocked(): void
    {
        [$schoolA, , $managerA] = $this->makeSchool('G');

        try {
            DB::table('school_employees')->where('id', $managerA->id)
                ->update(['manager_id' => $managerA->id]);
            $this->fail('The database accepted self-reporting.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }

        $this->assertNull($managerA->fresh()->manager_id);
    }

    public function test_integrity_migration_can_be_reapplied_safely_after_partial_ddl(): void
    {
        // A prior MySQL run may have created composite keys before failing
        // on the unsupported AUTO_INCREMENT CHECK expression.
        $migration = require database_path(
            'migrations/2026_11_09_120000_harden_school_employee_foreign_keys.php'
        );
        $migration->up();
        $migration->up();

        $this->test_valid_same_school_relationships_still_work();
    }

    public function test_mysql_self_reporting_uses_a_trigger_not_an_auto_increment_check(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL trigger metadata applies only to MySQL.');
        }

        $this->assertTrue(DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
            ->where('TRIGGER_NAME', 'school_employee_self_manager_update')
            ->exists());
        $this->assertTrue(DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
            ->where('TRIGGER_NAME', 'school_employee_self_manager_insert')
            ->exists());
    }

    public function test_valid_same_school_relationships_still_work(): void
    {
        [$school, $department, $manager] = $this->makeSchool('H');

        $id = DB::table('school_employees')->insertGetId(
            $this->row($school->id, 'VALID29', $manager->id, $department->id)
        );
        $this->assertDatabaseHas('school_employees', [
            'id' => $id,
            'school_id' => $school->id,
            'manager_id' => $manager->id,
            'school_department_id' => $department->id,
        ]);
    }
}
