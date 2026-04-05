<?php

namespace App\Domain\Venue\Models;

use App\Concerns\HasModelCache;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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

    protected static function newFactory(): VenueFactory
    {
        return VenueFactory::new();
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VenueImage::class)->orderBy('sort_order');
    }
}
