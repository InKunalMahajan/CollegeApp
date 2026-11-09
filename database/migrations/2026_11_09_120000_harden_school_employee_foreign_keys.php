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

    private function mysqlIndexExists(string $name): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'school_employees')
            ->where('INDEX_NAME', $name)->exists();
    }

    private function mysqlForeignKeyExists(string $name): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'school_employees')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->where('CONSTRAINT_NAME', $name)->exists();
    }

    private function mysqlTriggerExists(string $name): bool
    {
        return DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
            ->where('TRIGGER_NAME', $name)->exists();
    }

    public function up(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Phase 29 supports MySQL and SQLite only.');
        }

        $this->assertCleanData();

        if ($driver === 'mysql') {
            // MySQL DDL commits independently: the first failed Phase 29 run
            // may already have created the unique key and both composite FKs.
            // Detect those exact named objects and only add missing objects.
            if (! $this->mysqlIndexExists('school_employee_school_id_unique')) {
                Schema::table('school_employees', function (Blueprint $table) {
                    $table->unique(['school_id', 'id'], 'school_employee_school_id_unique');
                });
            }

            if (! $this->mysqlForeignKeyExists('school_employee_manager_school_fk')) {
                Schema::table('school_employees', function (Blueprint $table) {
                    $table->foreign(
                        ['school_id', 'manager_id'], 'school_employee_manager_school_fk'
                    )->references(['school_id', 'id'])
                        ->on('school_employees')->restrictOnDelete()->restrictOnUpdate();
                });
            }

            if (! $this->mysqlForeignKeyExists('school_employee_department_school_fk')) {
                Schema::table('school_employees', function (Blueprint $table) {
                    $table->foreign(
                        ['school_id', 'school_department_id'], 'school_employee_department_school_fk'
                    )->references(['school_id', 'id'])
                        ->on('school_departments')->restrictOnDelete()->restrictOnUpdate();
                });
            }

            // MySQL 8.4/9.x disallows AUTO_INCREMENT column 'id' in CHECK.
            // A trigger blocks direct self-manager edits without weakening
            // the two composite school ownership foreign keys.
            if (! $this->mysqlTriggerExists('school_employee_self_manager_update')) {
                DB::unprepared(<<<'SQL'
CREATE TRIGGER school_employee_self_manager_update
BEFORE UPDATE ON school_employees
FOR EACH ROW
BEGIN
    IF NEW.manager_id IS NOT NULL AND NEW.manager_id = NEW.id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Employee cannot manage themselves';
    END IF;
END
SQL);
            }

            if (! $this->mysqlTriggerExists('school_employee_self_manager_insert')) {
                DB::unprepared(<<<'SQL'
CREATE TRIGGER school_employee_self_manager_insert
BEFORE INSERT ON school_employees
FOR EACH ROW
BEGIN
    IF NEW.manager_id IS NOT NULL AND NEW.id IS NOT NULL
        AND NEW.id <> 0 AND NEW.manager_id = NEW.id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Employee cannot manage themselves';
    END IF;
END
SQL);
            }

            return;
        }

        // SQLite cannot safely add a composite FK to an existing table with
        // ALTER TABLE. Triggers provide equivalent school ownership enforcement
        // without rebuilding or deleting an existing self-referential table.
        DB::unprepared(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS school_employee_school_insert
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
CREATE TRIGGER IF NOT EXISTS school_employee_school_update
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
CREATE TRIGGER IF NOT EXISTS school_department_employee_school_update
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
            DB::statement('DROP TRIGGER IF EXISTS school_employee_self_manager_update');
            DB::statement('DROP TRIGGER IF EXISTS school_employee_self_manager_insert');

            if ($this->mysqlForeignKeyExists('school_employee_manager_school_fk')) {
                Schema::table('school_employees', fn (Blueprint $table) =>
                    $table->dropForeign('school_employee_manager_school_fk'));
            }
            if ($this->mysqlForeignKeyExists('school_employee_department_school_fk')) {
                Schema::table('school_employees', fn (Blueprint $table) =>
                    $table->dropForeign('school_employee_department_school_fk'));
            }
            if ($this->mysqlIndexExists('school_employee_school_id_unique')) {
                Schema::table('school_employees', fn (Blueprint $table) =>
                    $table->dropUnique('school_employee_school_id_unique'));
            }
        } elseif ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS school_employee_school_insert');
            DB::statement('DROP TRIGGER IF EXISTS school_employee_school_update');
            DB::statement('DROP TRIGGER IF EXISTS school_department_employee_school_update');
        } else {
            throw new RuntimeException('Phase 29 supports MySQL and SQLite only.');
        }
    }
};
