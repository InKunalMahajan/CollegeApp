<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('admission_number', 40)->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->unique();
            $table->date('admission_date');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'graduated'])->default('active')->index();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('departments');
    }
};
