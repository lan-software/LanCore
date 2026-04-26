<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see docs/mil-std-498/SRS.md ACH-F-008
 * @see docs/mil-std-498/DBDD.md §4.11.1 achievements
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table): void {
            $table->unsignedInteger('earned_user_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table): void {
            $table->dropColumn('earned_user_count');
        });
    }
};
