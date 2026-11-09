<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('employee_number', 40);
            $table->string('display_name', 160);
            $table->string('job_title', 160);
            $table->string('department_label', 180)->nullable();
            $table->string('location', 100)->nullable();
            $table->foreignId('school_department_id')->nullable()
                ->constrained('school_departments')->restrictOnDelete();
            $table->foreignId('manager_id')->nullable()
                ->constrained('school_employees')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'employee_number']);
            $table->index(['school_id', 'manager_id']);
            $table->index(['school_id', 'school_department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_employees');
    }
};
