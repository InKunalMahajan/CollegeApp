<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('email')->nullable();
            $table->timestamps();
        });
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->string('city')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name', 30);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });
        Schema::create('class_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->timestamps();
            $table->unique(['campus_id', 'academic_year_id', 'code'], 'class_sections_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sections');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('campuses');
        Schema::dropIfExists('schools');
    }
};
