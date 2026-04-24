<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see docs/mil-std-498/SRS.md SET-F-018
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seat_plan_blocks', function (Blueprint $table): void {
            $table->string('seat_title_prefix', 16)->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('seat_plan_blocks', function (Blueprint $table): void {
            $table->dropColumn('seat_title_prefix');
        });
    }
};
