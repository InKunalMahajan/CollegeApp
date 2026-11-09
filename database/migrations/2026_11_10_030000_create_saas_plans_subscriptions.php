<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->unsignedInteger('price_paise')->default(0);
            $table->string('currency', 3)->default('INR');
            $table->unsignedInteger('max_campuses')->nullable();
            $table->unsignedInteger('max_active_students')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('saas_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('saas_plan_id')->constrained('saas_plans')->restrictOnDelete();
            $table->string('status', 20)->default('trialing')->index();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('saas_subscriptions');
        Schema::dropIfExists('saas_plans');
    }
};
