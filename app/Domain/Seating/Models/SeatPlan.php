<?php

namespace App\Domain\Seating\Models;

use App\Domain\Event\Models\Event;
use Database\Factories\SeatPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['name', 'event_id', 'data'])]
class SeatPlan extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<SeatPlanFactory> */
    use HasFactory;

    protected static function newFactory(): SeatPlanFactory
    {
        return SeatPlanFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
