<?php

namespace App\Domain\Venue\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @see docs/mil-std-498/SRS.md EVT-F-006
 */
#[Fillable(['street', 'city', 'zip_code', 'state', 'country'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    public function venue(): HasOne
    {
        return $this->hasOne(Venue::class);
    }
}
