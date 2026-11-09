<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saas_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('saas_subscription_id')->constrained('saas_subscriptions')->restrictOnDelete();
            $table->unsignedBigInteger('amount_paise');
            $table->string('currency',3)->default('INR');
            $table->string('status',20)->default('draft')->index();
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('saas_invoices'); }
};
