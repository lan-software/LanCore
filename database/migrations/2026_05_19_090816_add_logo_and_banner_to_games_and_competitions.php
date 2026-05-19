<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
        });
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->text('rules_markdown')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'banner_path']);
        });
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'banner_path', 'rules_markdown']);
        });
    }
};
