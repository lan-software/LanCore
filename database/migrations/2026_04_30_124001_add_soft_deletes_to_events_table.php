<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Events become soft-deletable. Hard deletion is forbidden by EventPolicy::forceDelete
 * to preserve attendance, financial, and competition history.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-008
 * @see docs/mil-std-498/SRS.md DL-F-018
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
