<?php

namespace App\Domain\Seating\Models;

use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Database\Factories\SeatAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SRS.md SET-F-006, SET-F-007, SET-F-008
 * @see docs/mil-std-498/SDD.md §5.3c Seating Domain Design
 */
#[Fillable(['ticket_id', 'user_id', 'seat_plan_id', 'seat_plan_seat_id'])]
class SeatAssignment extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<SeatAssignmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['seat_title'];

    protected static function newFactory(): SeatAssignmentFactory
    {
        return SeatAssignmentFactory::new();
    }

    /**
     * Human-readable seat label (e.g. "VIP-A1"). If the seat's block carries
     * a `seat_title_prefix`, it is prepended to the raw seat title. Callers
     * should eager-load `seat.block` to avoid N+1.
     */
    protected function seatTitle(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (! $this->relationLoaded('seat') || $this->seat === null) {
                    return null;
                }

                $title = $this->seat->title;
                if (! is_string($title) || $title === '') {
                    return null;
                }

                $prefix = null;
                if ($this->seat->relationLoaded('block') && $this->seat->block !== null) {
                    $prefix = $this->seat->block->seat_title_prefix;
                }

                return (is_string($prefix) ? $prefix : '').$title;
            },
        );
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

    public function seat(): BelongsTo
    {
        return $this->belongsTo(SeatPlanSeat::class, 'seat_plan_seat_id');
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
