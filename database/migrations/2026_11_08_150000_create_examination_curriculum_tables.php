<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->date('exam_date');
            $table->unsignedInteger('max_marks');
            $table->unsignedInteger('pass_marks');
            $table->timestamps();
            $table->unique(['class_section_id', 'subject_id', 'name'], 'exam_class_subject_name_unique');
        });

        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_enrollment_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('marks_obtained')->nullable();
            $table->string('status', 15)->default('graded');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['examination_id', 'class_enrollment_id'], 'exam_enrollment_unique');
        });

        Schema::create('curriculum_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->unsignedInteger('sequence');
            $table->text('learning_outcome')->nullable();
            $table->timestamps();
            $table->unique(['subject_id', 'sequence'], 'curriculum_subject_sequence_unique');
        });

        Schema::create('curriculum_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_unit_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->unsignedInteger('sequence');
            $table->unsignedInteger('estimated_minutes')->default(45);
            $table->text('objective')->nullable();
            $table->timestamps();
            $table->unique(['curriculum_unit_id', 'sequence'], 'curriculum_lesson_sequence_unique');
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_lesson_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_subject_assignment_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('not_started');
            $table->date('completed_on')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['curriculum_lesson_id', 'teacher_subject_assignment_id'], 'lesson_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('curriculum_lessons');
        Schema::dropIfExists('curriculum_units');
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('examinations');
    }
};
