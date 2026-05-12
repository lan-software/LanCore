<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->json('signup_rules')->nullable()->after('description');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->json('signup_rules')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('signup_rules');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('signup_rules');
        });
    }
};
