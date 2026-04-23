<?php

namespace App\Domain\Seating\Models;

use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Database\Factories\SeatAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SRS.md SET-F-006, SET-F-007, SET-F-008
 * @see docs/mil-std-498/SDD.md §3.6 Seating
 */
#[Fillable(['ticket_id', 'user_id', 'seat_plan_id', 'seat_id'])]
class SeatAssignment extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<SeatAssignmentFactory> */
    use HasFactory;

    protected static function newFactory(): SeatAssignmentFactory
    {
        return SeatAssignmentFactory::new();
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seatPlan(): BelongsTo
    {
        return $this->belongsTo(SeatPlan::class);
    }

    /**
     * Restrict the query to assignments whose seat plan belongs to the given event.
     *
     * @param  Builder<SeatAssignment>  $query
     */
    public function scopeForEvent(Builder $query, int $eventId): Builder
    {
        return $query->whereHas('seatPlan', fn (Builder $plan) => $plan->where('event_id', $eventId));
    }
}
