<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Event\Models\Event;
use Database\Factories\TicketGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'event_id'])]
class TicketGroup extends Model
{
    /** @use HasFactory<TicketGroupFactory> */
    use HasFactory;

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
