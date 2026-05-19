<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->unsignedInteger('avg_match_minutes')->nullable()->after('description');
            $table->unsignedInteger('avg_stage_minutes')->nullable()->after('avg_match_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['avg_match_minutes', 'avg_stage_minutes']);
        });
    }
};
