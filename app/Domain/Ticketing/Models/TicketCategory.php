<?php

namespace App\Domain\Ticketing\Models;

use App\Concerns\HasModelCache;
use App\Domain\Event\Models\Event;
use Database\Factories\TicketCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['name', 'description', 'sort_order', 'event_id'])]
class TicketCategory extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<TicketCategoryFactory> */
    use HasFactory, HasModelCache;

    /**
     * @return Builder<static>
     */
    protected static function dropdownQuery(): Builder
    {
        return static::query()->orderBy('sort_order');
    }

    protected static function newFactory(): TicketCategoryFactory
    {
        return TicketCategoryFactory::new();
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
