<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->unsignedInteger('match_length_minutes')->nullable()->after('avg_stage_minutes');
        });
        Schema::table('game_modes', function (Blueprint $table) {
            $table->unsignedInteger('match_length_minutes')->nullable()->after('parameters');
        });
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedInteger('match_length_minutes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('match_length_minutes');
        });
        Schema::table('game_modes', function (Blueprint $table) {
            $table->dropColumn('match_length_minutes');
        });
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('match_length_minutes');
        });
    }
};
