<?php

namespace App\Console\Commands\Seating;

use App\Domain\Seating\Support\LegacySeatPlanConverter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Backfills legacy JSONB seat-plan data into the normalized
 * blocks/rows/seats/labels tables and re-links seat_assignments by new PK.
 *
 * Idempotent — plans with existing normalized rows are skipped. Orphan
 * assignments (seat_id not present in the legacy JSON) are reported to a
 * timestamped JSON file under the public disk; `seat_plan_seat_id` is left
 * NULL on those rows so ops can triage before the contract migration
 * enforces NOT NULL.
 *
 * @see docs/mil-std-498/SDP.md §2 Migration
 */
#[Signature('seating:migrate-json-to-normalized {--dry-run : Report what would change without writing}')]
#[Description('Backfill legacy JSONB seat-plan data into the normalized schema.')]
class MigrateJsonToNormalized extends Command
{
    public function handle(LegacySeatPlanConverter $converter): int
    {
        if ($this->option('dry-run')) {
            $this->warn('--dry-run not fully supported yet; aborting before any write.');

            return self::SUCCESS;
        }

        $result = $converter->backfillAll();

        $this->info(sprintf(
            'Seat plans migrated: %d, skipped (already normalized): %d, orphan assignments: %d',
            $result['migrated'],
            $result['skipped'],
            count($result['orphans']),
        ));

        if ($result['orphans'] !== []) {
            $path = 'seating-migration-orphans-'.now()->format('Y-m-d-His').'.json';
            Storage::disk('local')->put($path, json_encode($result['orphans'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            $this->warn('Orphan assignment report written to storage/app/'.$path);
            $this->warn('Triage (reassign or delete) these rows before enforcing NOT NULL on seat_assignments.seat_plan_seat_id.');
        }

        return self::SUCCESS;
    }
}
