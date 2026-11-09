<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refuse to change constraints if any existing relationship is outside
     * the owning school. Never silently null, move, or delete employee data.
     */
    private function assertCleanData(): void
    {
        $invalidManagers = DB::table('school_employees as e')
            ->leftJoin('school_employees as m', 'm.id', '=', 'e.manager_id')
            ->whereNotNull('e.manager_id')
            ->where(function ($query) {
                $query->whereNull('m.id')->orWhereColumn('m.school_id', '<>', 'e.school_id');
            })->exists();

        $invalidDepartments = DB::table('school_employees as e')
            ->leftJoin('school_departments as d', 'd.id', '=', 'e.school_department_id')
            ->whereNotNull('e.school_department_id')
            ->where(function ($query) {
                $query->whereNull('d.id')->orWhereColumn('d.school_id', '<>', 'e.school_id');
            })->exists();

        $selfManaged = DB::table('school_employees')
            ->whereNotNull('manager_id')->whereColumn('id', 'manager_id')->exists();

        if ($invalidManagers || $invalidDepartments || $selfManaged) {
            throw new RuntimeException(
                'Phase 29 blocked: employee manager or department belongs to another school, '.
                'references a missing row, or an employee manages themselves. '.
                'Correct existing data manually after a verified backup; no rows were modified.'
            );
        }
    }

    public function up(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Phase 29 supports MySQL and SQLite only.');
        }

        $this->assertCleanData();

        if ($driver === 'mysql') {
            // The old single-column FKs remain in place. These additional
            // composite FKs enforce ownership even for raw SQL and future jobs.
            Schema::table('school_employees', function (Blueprint $table) {
                $table->unique(['school_id', 'id'], 'school_employee_school_id_unique');
            });

            Schema::table('school_employees', function (Blueprint $table) {
                $table->foreign(
                    ['school_id', 'manager_id'],
                    'school_employee_manager_school_fk'
                )->references(['school_id', 'id'])
                    ->on('school_employees')->restrictOnDelete()->restrictOnUpdate();

                $table->foreign(
                    ['school_id', 'school_department_id'],
                    'school_employee_department_school_fk'
                )->references(['school_id', 'id'])
                    ->on('school_departments')->restrictOnDelete()->restrictOnUpdate();
            });

            // MySQL CHECK prevents self-reporting even through SQL.
            DB::statement(
                'ALTER TABLE school_employees ADD CONSTRAINT school_employee_not_self_chk '.
                'CHECK (manager_id IS NULL OR manager_id <> id)'
            );

            return;
        }

        // SQLite cannot safely add a composite FK to an existing table with
        // ALTER TABLE. Triggers provide equivalent school ownership enforcement
        // without rebuilding or deleting an existing self-referential table.
        DB::unprepared(<<<'SQL'
CREATE TRIGGER school_employee_school_insert
BEFORE INSERT ON school_employees
FOR EACH ROW
WHEN
    (NEW.manager_id IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM school_employees m
        WHERE m.id = NEW.manager_id AND m.school_id = NEW.school_id
    ))
    OR
    (NEW.school_department_id IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM school_departments d
        WHERE d.id = NEW.school_department_id AND d.school_id = NEW.school_id
    ))
    OR (NEW.manager_id IS NOT NULL AND NEW.manager_id = NEW.id)
BEGIN
    SELECT RAISE(ABORT, 'school_employee school relationship violation');
END;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER school_employee_school_update
BEFORE UPDATE OF id, school_id, manager_id, school_department_id ON school_employees
FOR EACH ROW
WHEN
    (NEW.manager_id IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM school_employees m
        WHERE m.id = NEW.manager_id AND m.school_id = NEW.school_id
    ))
    OR
    (NEW.school_department_id IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM school_departments d
        WHERE d.id = NEW.school_department_id AND d.school_id = NEW.school_id
    ))
    OR (NEW.manager_id IS NOT NULL AND NEW.manager_id = NEW.id)
    OR EXISTS (
        SELECT 1 FROM school_employees child
        WHERE child.manager_id = OLD.id AND child.school_id <> NEW.school_id
    )
BEGIN
    SELECT RAISE(ABORT, 'school_employee school relationship violation');
END;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER school_department_employee_school_update
BEFORE UPDATE OF id, school_id ON school_departments
FOR EACH ROW
WHEN EXISTS (
    SELECT 1 FROM school_employees e
    WHERE e.school_department_id = OLD.id AND e.school_id <> NEW.school_id
)
BEGIN
    SELECT RAISE(ABORT, 'school_department school relationship violation');
END;
SQL);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement(
                'ALTER TABLE school_employees DROP CHECK school_employee_not_self_chk'
            );
            Schema::table('school_employees', function (Blueprint $table) {
                $table->dropForeign('school_employee_manager_school_fk');
                $table->dropForeign('school_employee_department_school_fk');
            });
            Schema::table('school_employees', function (Blueprint $table) {
                $table->dropUnique('school_employee_school_id_unique');
            });
        } elseif ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS school_employee_school_insert');
            DB::statement('DROP TRIGGER IF EXISTS school_employee_school_update');
            DB::statement('DROP TRIGGER IF EXISTS school_department_employee_school_update');
        } else {
            throw new RuntimeException('Phase 29 supports MySQL and SQLite only.');
        }
    }
};
