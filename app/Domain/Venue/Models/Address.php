<?php

namespace App\Domain\Venue\Models;

use App\Concerns\HasModelCache;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @see docs/mil-std-498/SRS.md EVT-F-006, PUB-F-001
 */
#[Fillable(['street', 'city', 'zip_code', 'state', 'country', 'latitude', 'longitude', 'country_code'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory, HasModelCache;

    use HasUlids;

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * An address feeds into the published LAN Party Publishing Standard
     * document via its venue, so flush that cache when it changes.
     *
     * @return array<int, string>
     */
    public static function relatedCacheGroups(): array
    {
        return ['lpps'];
    }

    public function venue(): HasOne
    {
        return $this->hasOne(Venue::class);
    }
}
