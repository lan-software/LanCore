<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Models\User;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-005, CAP-TKT-006, CAP-TKT-011
 * @see docs/mil-std-498/SRS.md TKT-F-004, TKT-F-005, TKT-F-006, TKT-F-014
 */
#[Fillable([
    'status', 'checked_in_at', 'validation_id',
    'ticket_type_id', 'event_id', 'order_id',
    'owner_id', 'manager_id',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (empty($ticket->validation_id)) {
                $ticket->validation_id = self::generateValidationId();
            }
        });
    }

    public static function generateValidationId(): string
    {
        do {
            $id = strtoupper(Str::random(16));
        } while (self::where('validation_id', $id)->exists());

        return $id;
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'checked_in_at' => 'datetime',
        ];
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_user')
            ->withPivot('checked_in_at')
            ->withTimestamps();
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'ticket_ticket_addon', 'ticket_id', 'ticket_addon_id')
            ->withPivot('price_paid', 'order_id')
            ->withTimestamps();
    }
}
