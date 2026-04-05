<?php

namespace App\Domain\Ticketing\Models;

use App\Concerns\HasModelCache;
use App\Domain\Event\Models\Event;
use Database\Factories\TicketGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-002
 * @see docs/mil-std-498/SRS.md TKT-F-003
 */
#[Fillable(['name', 'description', 'event_id'])]
class TicketGroup extends Model
{
    /** @use HasFactory<TicketGroupFactory> */
    use HasFactory, HasModelCache;

    /**
     * @return array<int, string>
     */
    protected static function dropdownColumns(): array
    {
        return ['id', 'name', 'event_id'];
    }

    protected static function newFactory(): TicketGroupFactory
    {
        return TicketGroupFactory::new();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }
}
