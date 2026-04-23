<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Models\User;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-005, CAP-TKT-006, CAP-TKT-011
 * @see docs/mil-std-498/SRS.md TKT-F-004, TKT-F-005, TKT-F-006, TKT-F-014
 */
#[Fillable([
    'status', 'checked_in_at',
    'validation_nonce_hash', 'validation_kid',
    'validation_issued_at', 'validation_expires_at',
    'validation_rotation_epoch',
    'ticket_type_id', 'event_id', 'order_id',
    'owner_id', 'manager_id',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * Rotate this ticket's signed LCT1 token.
     *
     * Increments the stored `validation_rotation_epoch`, recomputes the
     * deterministic nonce, persists the new nonce hash + kid + timestamps,
     * and returns the fresh QR payload for any caller that needs to dispatch
     * a PDF regeneration.
     *
     * Call sites: initial issuance (FulfillOrder), assignment/manager changes
     * and explicit rotate actions (UpdateTicketAssignments). Read-only paths
     * such as QR/PDF rendering MUST NOT call this.
     *
     * @see docs/mil-std-498/SDD.md §3.3.2
     * @see docs/mil-std-498/SRS.md TKT-F-019
     */
    public function rotateSignedToken(TicketTokenService $service): string
    {
        return $service->rotate($this)->qrPayload;
    }

    /**
     * Render the currently-valid QR payload without mutating any state.
     *
     * Deterministically rebuilds the same LCT1 token using the stored
     * rotation epoch, kid and issued/expires timestamps. Safe to call on
     * every request.
     *
     * @see docs/mil-std-498/SRS.md TKT-F-026
     */
    public function renderSignedToken(TicketTokenService $service): string
    {
        return $service->render($this);
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
            'validation_issued_at' => 'datetime',
            'validation_expires_at' => 'datetime',
            'validation_rotation_epoch' => 'integer',
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
