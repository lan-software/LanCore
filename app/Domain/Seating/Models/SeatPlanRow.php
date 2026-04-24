<?php

namespace App\Domain\Seating\Models;

use Database\Factories\SeatPlanRowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * An ordered grouping of seats inside a block, used for row-based seat
 * numbering.
 *
 * @see docs/mil-std-498/SRS.md SET-F-001
 */
#[Fillable(['seat_plan_block_id', 'name', 'sort_order'])]
class SeatPlanRow extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<SeatPlanRowFactory> */
    use HasFactory;

    protected static function newFactory(): SeatPlanRowFactory
    {
        return SeatPlanRowFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(SeatPlanBlock::class, 'seat_plan_block_id');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(SeatPlanSeat::class)->orderBy('number');
    }
}
