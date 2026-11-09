<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saas_onboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('organization_name', 255);
            $table->string('organization_code', 30);
            $table->string('contact_email', 255);
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('school_id')->nullable()->constrained('schools')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['requested_by', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('saas_onboarding_requests'); }
};
