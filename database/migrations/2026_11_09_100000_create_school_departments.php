<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('code', 32);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'code']);
            $table->unique(['school_id', 'id']);
        });

        Schema::create('school_department_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_department_id')->constrained('school_departments')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role', 32);
            $table->timestamps();
            $table->unique(['school_department_id', 'user_id'], 'department_user_unique');
            $table->foreign(['school_id', 'school_department_id'], 'department_membership_school_fk')
                ->references(['school_id', 'id'])->on('school_departments')->restrictOnDelete();
            $table->index(['school_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_department_memberships');
        Schema::dropIfExists('school_departments');
    }
};
