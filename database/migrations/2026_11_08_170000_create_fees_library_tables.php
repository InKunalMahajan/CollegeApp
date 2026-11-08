<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->unsignedBigInteger('amount_paise');
            $table->date('due_date');
            $table->timestamps();
            $table->unique(['class_section_id', 'name']);
        });
        Schema::create('fee_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_enrollment_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_paise');
            $table->timestamps();
            $table->unique(['fee_structure_id', 'class_enrollment_id']);
        });
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_charge_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_paise');
            $table->date('paid_on');
            $table->string('method', 25);
            $table->string('reference', 100)->nullable()->unique();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->string('author', 200)->nullable();
            $table->string('isbn', 20)->nullable();
            $table->timestamps();
        });
        Schema::create('library_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_book_id')->constrained()->restrictOnDelete();
            $table->string('barcode', 80)->unique();
            $table->string('status', 20)->default('available');
            $table->timestamps();
        });
        Schema::create('library_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_copy_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_enrollment_id')->constrained()->restrictOnDelete();
            $table->date('issued_on');
            $table->date('due_on');
            $table->date('returned_on')->nullable();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_copies');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('fee_charges');
        Schema::dropIfExists('fee_structures');
    }
};
