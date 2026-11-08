<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('school_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_subject_assignment_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->text('instructions');
            $table->dateTime('due_at');
            $table->unsignedInteger('max_marks')->default(100);
            $table->string('status', 15)->default('draft');
            $table->timestamps();
        });
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_enrollment_id')->constrained()->restrictOnDelete();
            $table->longText('response');
            $table->timestamp('submitted_at');
            $table->unsignedInteger('marks')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['school_assignment_id','class_enrollment_id'], 'assignment_enrollment_unique');
        });
        Schema::create('school_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 200);
            $table->text('body');
            $table->string('audience', 20)->default('all');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('school_announcements');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('school_assignments');
        Schema::dropIfExists('student_accounts');
    }
};
