<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('website_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('disabled_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('disabled_at'));
        Schema::dropIfExists('website_owners');
    }
};
