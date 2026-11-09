<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saas_brandings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $table->string('display_name',120);
            $table->string('primary_color',7)->default('#4F46E5');
            $table->timestamps();
        });
        Schema::create('saas_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->string('hostname',253)->unique();
            $table->string('status',24)->default('pending')->index();
            $table->string('verification_token',96)->unique();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('saas_domains');
        Schema::dropIfExists('saas_brandings');
    }
};
