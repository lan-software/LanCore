<?php

namespace App\Domain\Event\Models;

use App\Concerns\HasModelCache;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Competition\Models\Competition;
use App\Domain\Event\Enums\EventStatus;
use App\Domain\Program\Models\Program;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Shop\Models\Order;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Venue\Models\Venue;
use App\Models\User;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SSS.md CAP-EVT-001, CAP-EVT-002, CAP-EVT-005
 * @see docs/mil-std-498/SRS.md EVT-F-002, EVT-F-004, EVT-F-010
 */
#[Fillable(['name', 'description', 'start_date', 'end_date', 'banner_images', 'status', 'venue_id', 'primary_program_id', 'seat_capacity'])]
class Event extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<EventFactory> */
    use HasFactory, HasModelCache;

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
            'banner_images' => 'array',
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

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function remainingSeatCapacity(): int
    {
        if ($this->seat_capacity === null) {
            return PHP_INT_MAX;
        }

        $ticketSeats = (int) $this->tickets()
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->selectRaw('COALESCE(SUM(ticket_types.seats_per_user * ticket_types.max_users_per_ticket), 0) as total')
            ->value('total');

        $addonSeats = $this->tickets()
            ->join('ticket_ticket_addon', 'tickets.id', '=', 'ticket_ticket_addon.ticket_id')
            ->join('ticket_addons', 'ticket_ticket_addon.ticket_addon_id', '=', 'ticket_addons.id')
            ->sum('ticket_addons.seats_consumed');

        return max(0, $this->seat_capacity - $ticketSeats - (int) $addonSeats);
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

    /**
     * @param  Builder<self>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->where('end_date', '<', now());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('start_date', '<=', now())->where('end_date', '>=', now());
    }

    /**
     * Events the user has any participation in (ticket owner/manager/assignee, order, or competition team membership).
     *
     * @param  Builder<self>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $userId = $user->id;

        $query->where(function (Builder $q) use ($userId) {
            $q->whereHas('tickets', function (Builder $t) use ($userId) {
                $t->where('owner_id', $userId)
                    ->orWhere('manager_id', $userId)
                    ->orWhereHas('users', fn (Builder $u) => $u->where('users.id', $userId));
            })
                ->orWhereIn('id', Competition::query()
                    ->whereHas('teams.activeMembers', fn (Builder $m) => $m->where('user_id', $userId))
                    ->whereNotNull('event_id')
                    ->select('event_id'))
                ->orWhereIn('id', Order::query()->where('user_id', $userId)->whereNotNull('event_id')->select('event_id'));
        });
    }
}
