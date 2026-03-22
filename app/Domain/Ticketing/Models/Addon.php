<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Concerns\InteractsWithShop;
use App\Domain\Shop\Contracts\Purchasable;
use App\Domain\Shop\Contracts\PurchasableDependency;
use Database\Factories\AddonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'name', 'description', 'price', 'quota', 'seats_consumed',
    'requires_ticket', 'is_hidden', 'event_id',
])]
class Addon extends Model implements AuditableContract, Purchasable
{
    use Auditable;

    /** @use HasFactory<AddonFactory> */
    use HasFactory;

    use InteractsWithShop;

    protected $table = 'ticket_addons';

    protected static function newFactory(): AddonFactory
    {
        return AddonFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quota' => 'integer',
            'seats_consumed' => 'integer',
            'requires_ticket' => 'boolean',
            'is_hidden' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_ticket_addon', 'ticket_addon_id', 'ticket_id')
            ->withTimestamps();
    }

    public function isAvailableForPurchase(): bool
    {
        if ($this->is_hidden) {
            return false;
        }

        if ($this->quota !== null && $this->tickets()->count() >= $this->quota) {
            return false;
        }

        return true;
    }

    public function getMaxQuantity(): int
    {
        if ($this->quota === null) {
            return PHP_INT_MAX;
        }

        return max(0, $this->quota - $this->tickets()->count());
    }

    public function remainingQuota(): ?int
    {
        if ($this->quota === null) {
            return null;
        }

        return max(0, $this->quota - $this->tickets()->count());
    }

    /**
     * @return array<int, PurchasableDependency>
     */
    public function getPurchasableDependencies(): array
    {
        if (! $this->requires_ticket) {
            return [];
        }

        return [
            new PurchasableDependency(
                purchasableClass: TicketType::class,
                eventId: $this->event_id,
                message: "Addon '{$this->name}' requires a ticket for the same event.",
            ),
        ];
    }
}
