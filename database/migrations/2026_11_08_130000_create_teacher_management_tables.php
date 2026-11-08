<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('campus_id')->constrained()->restrictOnDelete();
            $table->string('employee_code', 40);
            $table->string('designation')->nullable();
            $table->date('joined_on')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'employee_code']);
        });

        Schema::create('teacher_subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_section_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'subject_id', 'class_section_id'], 'teacher_subject_unique');
        });

        Schema::create('class_teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_section_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_teacher_assignments');
        Schema::dropIfExists('teacher_subject_assignments');
        Schema::dropIfExists('teacher_profiles');
    }
};
