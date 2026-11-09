<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 32);
            $table->timestamps();
            $table->unique(['campus_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        // No historical records are automatically assigned to a campus.
        // An organization administrator must verify each campus assignment.
        Schema::table('school_employees', function (Blueprint $table) {
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->restrictOnDelete();
            $table->index(['school_id', 'campus_id'], 'school_employee_campus_scope_idx');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->restrictOnDelete();
            $table->index(['school_id', 'campus_id'], 'student_campus_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('student_campus_scope_idx');
            $table->dropConstrainedForeignId('campus_id');
        });
        Schema::table('school_employees', function (Blueprint $table) {
            $table->dropIndex('school_employee_campus_scope_idx');
            $table->dropConstrainedForeignId('campus_id');
        });
        Schema::dropIfExists('campus_memberships');
    }
};
