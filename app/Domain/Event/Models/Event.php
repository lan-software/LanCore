<?php

namespace App\Domain\Event\Models;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Program\Models\Program;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Venue\Models\Venue;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'start_date', 'end_date', 'banner_image', 'status', 'venue_id', 'primary_program_id', 'seat_capacity'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'status' => EventStatus::class,
            'seat_capacity' => 'integer',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class)->orderBy('sort_order');
    }

    public function primaryProgram(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'primary_program_id');
    }

    public function sponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class)->withTimestamps();
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(Addon::class);
    }

    public function seatPlans(): HasMany
    {
        return $this->hasMany(SeatPlan::class);
    }

    public function remainingSeatCapacity(): int
    {
        if ($this->seat_capacity === null) {
            return PHP_INT_MAX;
        }

        $ticketSeats = $this->tickets()
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->sum('ticket_types.seats_per_ticket');

        $addonSeats = $this->tickets()
            ->join('ticket_ticket_addon', 'tickets.id', '=', 'ticket_ticket_addon.ticket_id')
            ->join('ticket_addons', 'ticket_ticket_addon.ticket_addon_id', '=', 'ticket_addons.id')
            ->sum('ticket_addons.seats_consumed');

        return max(0, $this->seat_capacity - (int) $ticketSeats - (int) $addonSeats);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', EventStatus::Published);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('start_date', '>', now());
    }
}
