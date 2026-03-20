<?php

namespace App\Domain\Venue\Models;

use Database\Factories\VenueImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['venue_id', 'path', 'alt_text', 'sort_order'])]
class VenueImage extends Model
{
    /** @use HasFactory<VenueImageFactory> */
    use HasFactory;

    protected static function newFactory(): VenueImageFactory
    {
        return VenueImageFactory::new();
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
