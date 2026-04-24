<?php

namespace App\Domain\Seating\Models;

use Database\Factories\SeatPlanSeatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Physical seat inside a seat plan. The `seat_plan_id` column is denormalized
 * (derivable from block → plan) so hot-path queries ("find seat N in plan X",
 * "list plan seats") avoid a join. Consistency is enforced on save.
 *
 * @see docs/mil-std-498/SRS.md SET-F-002, SET-F-006
 */
#[Fillable([
    'seat_plan_id',
    'seat_plan_block_id',
    'seat_plan_row_id',
    'number',
    'title',
    'x',
    'y',
    'salable',
    'color',
    'note',
    'custom_data',
])]
class SeatPlanSeat extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<SeatPlanSeatFactory> */
    use HasFactory;

    protected static function newFactory(): SeatPlanSeatFactory
    {
        return SeatPlanSeatFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'x' => 'integer',
            'y' => 'integer',
            'number' => 'integer',
            'salable' => 'boolean',
            'custom_data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SeatPlanSeat $seat): void {
            if ($seat->seat_plan_block_id === null) {
                return;
            }

            $block = $seat->block()->first();

            if ($block !== null && $block->seat_plan_id !== $seat->seat_plan_id) {
                $seat->seat_plan_id = $block->seat_plan_id;
            }
        });
    }

    public function seatPlan(): BelongsTo
    {
        return $this->belongsTo(SeatPlan::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(SeatPlanBlock::class, 'seat_plan_block_id');
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(SeatPlanRow::class, 'seat_plan_row_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SeatAssignment::class);
    }
}
