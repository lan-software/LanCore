<?php

use App\Domain\Seating\Support\LegacySeatPlanConverter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contract step of the JSON → normalized seating migration. Runs the legacy
 * backfill in-place (idempotent, no-op on fresh installs with no seat_plans
 * rows), then drops `seat_plans.data` and `seat_assignments.seat_id`,
 * tightens `seat_plan_seat_id` to NOT NULL and adds the new unique
 * constraint.
 *
 * @see docs/mil-std-498/SDP.md §2 Migration
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('seat_plans', 'data') && DB::table('seat_plans')->exists()) {
            app(LegacySeatPlanConverter::class)->backfillAll();
        }

        $orphans = DB::table('seat_assignments')->whereNull('seat_plan_seat_id')->count();

        if ($orphans > 0) {
            throw new RuntimeException(sprintf(
                'Aborting contract migration: %d seat_assignment row(s) have no seat_plan_seat_id. Run `php artisan seating:migrate-json-to-normalized`, triage the reported orphans, then re-run the migration.',
                $orphans,
            ));
        }

        Schema::table('seat_assignments', function (Blueprint $table): void {
            $table->dropUnique(['seat_plan_id', 'seat_id']);
            $table->dropColumn('seat_id');
        });

        Schema::table('seat_assignments', function (Blueprint $table): void {
            $table->foreignId('seat_plan_seat_id')->nullable(false)->change();
            $table->unique(['seat_plan_id', 'seat_plan_seat_id']);
        });

        Schema::table('seat_plans', function (Blueprint $table): void {
            $table->dropColumn('data');
        });
    }

    public function down(): void
    {
        Schema::table('seat_plans', function (Blueprint $table): void {
            $table->jsonb('data')->default('{"blocks": []}');
        });

        Schema::table('seat_assignments', function (Blueprint $table): void {
            $table->dropUnique(['seat_plan_id', 'seat_plan_seat_id']);
            $table->foreignId('seat_plan_seat_id')->nullable()->change();
            $table->string('seat_id', 64)->nullable();
            $table->unique(['seat_plan_id', 'seat_id']);
        });
    }
};
