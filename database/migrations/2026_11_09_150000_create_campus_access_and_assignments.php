<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Phase 34 supports MySQL and SQLite only.');
        }

        Schema::create('campus_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 32);
            $table->timestamps();
            $table->unique(['campus_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        // IMPORTANT: Add a plain nullable column, NOT constrained(), so
        // SQLite uses ADD COLUMN instead of rebuilding school_employees.
        // That table already has Phase 29 security triggers referenced from
        // school_departments; a rebuild breaks those triggers.
        Schema::table('school_employees', function (Blueprint $table) {
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->index(['school_id', 'campus_id'], 'school_employee_campus_scope_idx');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->index(['school_id', 'campus_id'], 'student_campus_scope_idx');
        });

        if ($driver === 'mysql') {
            Schema::table('campuses', function (Blueprint $table) {
                $table->unique(['school_id', 'id'], 'campus_school_id_unique');
            });
            Schema::table('school_employees', function (Blueprint $table) {
                $table->foreign(['school_id', 'campus_id'], 'school_employee_campus_owner_fk')
                    ->references(['school_id', 'id'])->on('campuses')
                    ->restrictOnDelete()->restrictOnUpdate();
            });
            Schema::table('students', function (Blueprint $table) {
                $table->foreign(['school_id', 'campus_id'], 'student_campus_owner_fk')
                    ->references(['school_id', 'id'])->on('campuses')
                    ->restrictOnDelete()->restrictOnUpdate();
            });

            return;
        }

        // Equivalent ownership constraints for SQLite without rebuilding
        // any legacy employee/student table.
        DB::unprepared(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS employee_campus_owner_insert
BEFORE INSERT ON school_employees FOR EACH ROW
WHEN NEW.campus_id IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM campuses c WHERE c.id = NEW.campus_id AND c.school_id = NEW.school_id
)
BEGIN
    SELECT RAISE(ABORT, 'Employee campus must belong to the same school');
END;
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS employee_campus_owner_update
BEFORE UPDATE OF campus_id, school_id ON school_employees FOR EACH ROW
WHEN NEW.campus_id IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM campuses c WHERE c.id = NEW.campus_id AND c.school_id = NEW.school_id
)
BEGIN
    SELECT RAISE(ABORT, 'Employee campus must belong to the same school');
END;
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS student_campus_owner_insert
BEFORE INSERT ON students FOR EACH ROW
WHEN NEW.campus_id IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM campuses c WHERE c.id = NEW.campus_id AND c.school_id = NEW.school_id
)
BEGIN
    SELECT RAISE(ABORT, 'Student campus must belong to the same school');
END;
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS student_campus_owner_update
BEFORE UPDATE OF campus_id, school_id ON students FOR EACH ROW
WHEN NEW.campus_id IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM campuses c WHERE c.id = NEW.campus_id AND c.school_id = NEW.school_id
)
BEGIN
    SELECT RAISE(ABORT, 'Student campus must belong to the same school');
END;
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS campus_owner_update
BEFORE UPDATE OF id, school_id ON campuses FOR EACH ROW
WHEN EXISTS (
    SELECT 1 FROM school_employees e
    WHERE e.campus_id = OLD.id AND e.school_id <> NEW.school_id
) OR EXISTS (
    SELECT 1 FROM students s
    WHERE s.campus_id = OLD.id AND s.school_id <> NEW.school_id
)
BEGIN
    SELECT RAISE(ABORT, 'Campus cannot move away from assigned records');
END;
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS campus_owner_delete
BEFORE DELETE ON campuses FOR EACH ROW
WHEN EXISTS (
    SELECT 1 FROM school_employees e WHERE e.campus_id = OLD.id
) OR EXISTS (
    SELECT 1 FROM students s WHERE s.campus_id = OLD.id
)
BEGIN
    SELECT RAISE(ABORT, 'Campus has employee or student records');
END;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite drops columns by rebuilding tables in some Laravel/SQLite
            // versions. Phase 29's existing triggers depend on school_employees,
            // so a partial rollback could corrupt the schema. Fail closed.
            throw new RuntimeException(
                'SQLite Phase 34 rollback is blocked to preserve Phase 29 triggers. '.
                'Restore a verified backup or use a reviewed forward migration.'
            );
        }

        if (DB::getDriverName() === 'mysql') {
            Schema::table('students', fn (Blueprint $table) =>
                $table->dropForeign('student_campus_owner_fk'));
            Schema::table('school_employees', fn (Blueprint $table) =>
                $table->dropForeign('school_employee_campus_owner_fk'));
            Schema::table('campuses', fn (Blueprint $table) =>
                $table->dropUnique('campus_school_id_unique'));
        } elseif (DB::getDriverName() === 'sqlite') {
            foreach ([
                'campus_owner_delete', 'campus_owner_update',
                'student_campus_owner_update', 'student_campus_owner_insert',
                'employee_campus_owner_update', 'employee_campus_owner_insert',
            ] as $trigger) {
                DB::statement('DROP TRIGGER IF EXISTS '.$trigger);
            }
        }

        // SQLite rollback drops columns without a complete schema reset in
        // recent SQLite/Laravel versions; review before using on live data.
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('student_campus_scope_idx');
            $table->dropColumn('campus_id');
        });
        Schema::table('school_employees', function (Blueprint $table) {
            $table->dropIndex('school_employee_campus_scope_idx');
            $table->dropColumn('campus_id');
        });
        Schema::dropIfExists('campus_memberships');
    }
};
