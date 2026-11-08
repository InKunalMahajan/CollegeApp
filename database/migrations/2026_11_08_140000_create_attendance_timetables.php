<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_section_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'class_section_id']);
        });
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_enrollment_id')->constrained()->restrictOnDelete();
            $table->date('attendance_date');
            $table->string('status', 15);
            $table->timestamps();
            $table->unique(['class_enrollment_id', 'attendance_date'], 'student_attendance_unique');
        });
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->date('attendance_date');
            $table->string('status', 15);
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'attendance_date'], 'staff_attendance_unique');
        });
        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room', 80)->nullable();
            $table->timestamps();
            $table->index(['class_section_id', 'day_of_week', 'starts_at']);
            $table->index(['teacher_profile_id', 'day_of_week', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('student_attendances');
        Schema::dropIfExists('class_enrollments');
    }
};
