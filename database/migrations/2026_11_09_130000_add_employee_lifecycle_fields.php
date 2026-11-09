<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_employees', function (Blueprint $table) {
            $table->string('employment_status', 24)->default('active');
            $table->string('employment_type', 24)->nullable();
            $table->date('joined_on')->nullable();
            $table->string('work_email', 255)->nullable();
            $table->index(['school_id', 'employment_status'], 'school_employees_status_idx');
            $table->unique(['school_id', 'work_email'], 'school_employee_work_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('school_employees', function (Blueprint $table) {
            $table->dropUnique('school_employee_work_email_unique');
            $table->dropIndex('school_employees_status_idx');
            $table->dropColumn(['employment_status', 'employment_type', 'joined_on', 'work_email']);
        });
    }
};
