<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saas_platform_appearances', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 16)->unique()->default('default');
            $table->string('primary_color', 7)->default('#4F46E5');
            $table->string('font_family', 24)->default('system');
            $table->unsignedTinyInteger('font_size')->default(16);
            $table->string('density', 16)->default('comfortable');
            $table->timestamps();
        });

        Schema::table('saas_brandings', function (Blueprint $table) {
            $table->string('font_family', 24)->default('system');
            $table->unsignedTinyInteger('font_size')->default(16);
            $table->string('density', 16)->default('comfortable');
        });
    }

    public function down(): void
    {
        Schema::table('saas_brandings', function (Blueprint $table) {
            $table->dropColumn(['font_family', 'font_size', 'density']);
        });
        Schema::dropIfExists('saas_platform_appearances');
    }
};
