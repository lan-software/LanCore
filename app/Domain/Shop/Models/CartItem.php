<?php

namespace App\Domain\Shop\Models;

use App\Domain\Shop\Contracts\Purchasable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-002
 */
#[Fillable(['cart_id', 'purchasable_type', 'purchasable_id', 'quantity'])]
class CartItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the line total in cents.
     */
    public function lineTotal(): int
    {
        if ($this->purchasable instanceof Purchasable) {
            return $this->purchasable->getUnitPrice() * $this->quantity;
        }

        return 0;
    }
}
