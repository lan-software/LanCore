<?php

namespace App\Domain\Seating\Models;

use Database\Factories\SeatPlanLabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Free-form text label on a seat-plan block (e.g. a row letter annotation).
 *
 * @see docs/mil-std-498/SRS.md SET-F-001
 */
#[Fillable(['seat_plan_id', 'seat_plan_block_id', 'title', 'x', 'y', 'sort_order'])]
class SeatPlanLabel extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<SeatPlanLabelFactory> */
    use HasFactory;

    protected static function newFactory(): SeatPlanLabelFactory
    {
        return SeatPlanLabelFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'x' => 'integer',
            'y' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(SeatPlanBlock::class, 'seat_plan_block_id');
    }

    public function seatPlan(): BelongsTo
    {
        return $this->belongsTo(SeatPlan::class);
    }
}
