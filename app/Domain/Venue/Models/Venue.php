<?php

namespace App\Domain\Venue\Models;

use App\Concerns\HasModelCache;
use App\Domain\Event\Models\Event;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @see docs/mil-std-498/SSS.md CAP-EVT-003
 * @see docs/mil-std-498/SRS.md EVT-F-006
 */
#[Fillable(['name', 'description', 'address_id'])]
class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory, HasModelCache;

    use HasUlids;

    protected static function newFactory(): VenueFactory
    {
        return VenueFactory::new();
    }

    /**
     * A venue is part of the published LAN Party Publishing Standard
     * document, so flush that cache when it changes.
     *
     * @return array<int, string>
     */
    public static function relatedCacheGroups(): array
    {
        return ['lpps'];
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VenueImage::class)->orderBy('sort_order');
    }
}
