<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Concerns\InteractsWithShop;
use App\Domain\Shop\Contracts\Purchasable;
use App\Domain\Ticketing\Enums\CheckInMode;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-001, CAP-TKT-003, CAP-TKT-009, CAP-TKT-010, CAP-TKT-011, CAP-TKT-012
 * @see docs/mil-std-498/SRS.md TKT-F-001, TKT-F-011, TKT-F-013, TKT-F-015
 */
#[Fillable([
    'name', 'description', 'price', 'quota', 'max_per_user', 'seats_per_ticket',
    'max_users_per_ticket', 'check_in_mode',
    'is_row_ticket', 'is_seatable', 'is_hidden',
    'purchase_from', 'purchase_until', 'is_locked',
    'event_id', 'ticket_category_id', 'ticket_group_id',
])]
class TicketType extends Model implements AuditableContract, Purchasable
{
    use Auditable;

    /** @use HasFactory<TicketTypeFactory> */
    use HasFactory;

    use InteractsWithShop;

    protected static function newFactory(): TicketTypeFactory
    {
        return TicketTypeFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quota' => 'integer',
            'max_per_user' => 'integer',
            'seats_per_ticket' => 'integer',
            'max_users_per_ticket' => 'integer',
            'check_in_mode' => CheckInMode::class,
            'is_row_ticket' => 'boolean',
            'is_seatable' => 'boolean',
            'is_hidden' => 'boolean',
            'is_locked' => 'boolean',
            'purchase_from' => 'datetime',
            'purchase_until' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    public function ticketGroup(): BelongsTo
    {
        return $this->belongsTo(TicketGroup::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function isGroupTicket(): bool
    {
        return $this->max_users_per_ticket > 1;
    }

    public function totalSeatsConsumed(): int
    {
        return $this->seats_per_ticket * $this->max_users_per_ticket;
    }

    public function isAvailableForPurchase(): bool
    {
        if ($this->is_hidden) {
            return false;
        }

        $now = now();

        if ($this->purchase_from && $now->isBefore($this->purchase_from)) {
            return false;
        }

        if ($this->purchase_until && $now->isAfter($this->purchase_until)) {
            return false;
        }

        return $this->tickets()->count() < $this->quota;
    }

    public function getMaxQuantity(): int
    {
        $remaining = $this->remainingQuota();

        if ($this->max_per_user !== null) {
            return min($remaining, $this->max_per_user);
        }

        return $remaining;
    }

    public function remainingQuota(): int
    {
        return max(0, $this->quota - $this->tickets()->count());
    }
}
