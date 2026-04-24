<?php

namespace App\Domain\Seating\Models;

use App\Domain\Event\Models\Event;
use Database\Factories\SeatPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Normalized seat plan. Blocks → rows → seats + labels live in their own
 * tables; the old JSONB blob was retired in the normalization migration.
 *
 * @see docs/mil-std-498/SRS.md SET-F-001, SET-F-002, SET-F-003
 * @see docs/mil-std-498/DBDD.md §4.5 Seating
 */
#[Fillable(['name', 'event_id', 'background_image_url'])]
class SeatPlan extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<SeatPlanFactory> */
    use HasFactory;

    protected static function newFactory(): SeatPlanFactory
    {
        return SeatPlanFactory::new();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(SeatPlanBlock::class)->orderBy('sort_order');
    }

    /**
     * Direct access to every seat in the plan, via the denormalized
     * `seat_plan_id` FK on `seat_plan_seats`. Avoids a hasManyThrough hop on
     * hot paths such as the AssignSeat validation.
     */
    public function seats(): HasMany
    {
        return $this->hasMany(SeatPlanSeat::class);
    }

    /**
     * @see docs/mil-std-498/SRS.md SET-F-013 (UpdateSeatPlan invalidation diff)
     */
    public function seatAssignments(): HasMany
    {
        return $this->hasMany(SeatAssignment::class);
    }

    /**
     * Labels scoped directly to the plan (no parent block). The library
     * requires labels under a block on the wire — SeatPlanResource flattens
     * these into the first block at serialisation time, but the editor
     * treats them as plan-owned (SET-F-020).
     */
    public function globalLabels(): HasMany
    {
        return $this->hasMany(SeatPlanLabel::class)
            ->whereNull('seat_plan_block_id')
            ->orderBy('sort_order');
    }
}
