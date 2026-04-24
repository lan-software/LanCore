<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow seat plan labels to live at the plan level (no parent block) by adding
 * a `seat_plan_id` FK and making `seat_plan_block_id` nullable. The
 * `@alisaitteke/seatmap-canvas` library still requires labels under a block
 * on the wire, so `SeatPlanResource` flattens plan-level labels into the
 * first block at serialisation time. In storage they stay plan-scoped so the
 * admin editor can keep them decoupled.
 *
 * @see docs/mil-std-498/SRS.md SET-F-020
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seat_plan_labels', function (Blueprint $table): void {
            $table->foreignId('seat_plan_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        DB::statement(
            'UPDATE seat_plan_labels SET seat_plan_id = (
                SELECT seat_plan_id FROM seat_plan_blocks WHERE seat_plan_blocks.id = seat_plan_labels.seat_plan_block_id
             ) WHERE seat_plan_id IS NULL AND seat_plan_block_id IS NOT NULL'
        );

        Schema::table('seat_plan_labels', function (Blueprint $table): void {
            $table->foreignId('seat_plan_id')->nullable(false)->change();
            $table->foreignId('seat_plan_block_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('seat_plan_labels')->whereNull('seat_plan_block_id')->delete();

        Schema::table('seat_plan_labels', function (Blueprint $table): void {
            $table->foreignId('seat_plan_block_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('seat_plan_id');
        });
    }
};
