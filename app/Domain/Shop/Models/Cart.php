<?php

namespace App\Domain\Shop\Models;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Contracts\Purchasable;
use App\Domain\Shop\Contracts\PurchasableDependency;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-001
 * @see docs/mil-std-498/SRS.md SHP-F-001, SHP-F-002
 */
#[Fillable(['user_id', 'event_id', 'voucher_code'])]
class Cart extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    public function subtotal(): int
    {
        $total = 0;

        foreach ($this->items()->with('purchasable')->get() as $item) {
            if ($item->purchasable instanceof Purchasable) {
                $total += $item->purchasable->getUnitPrice() * $item->quantity;
            }
        }

        return $total;
    }

    /**
     * Check whether the cart contains a purchasable of the given type.
     *
     * @param  class-string<Purchasable>  $purchasableClass
     */
    public function containsPurchasableOfType(string $purchasableClass, ?int $eventId = null): bool
    {
        return $this->items()->where('purchasable_type', $purchasableClass)->when(
            $eventId !== null,
            fn ($query) => $query->whereHasMorph('purchasable', [$purchasableClass], function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })
        )->exists();
    }

    /**
     * Validate that all purchasable dependencies in the cart are satisfied.
     *
     * @return string[]
     */
    public function validateDependencies(): array
    {
        $errors = [];

        foreach ($this->items()->with('purchasable')->get() as $item) {
            if (! $item->purchasable instanceof Purchasable) {
                continue;
            }

            foreach ($item->purchasable->getPurchasableDependencies() as $dependency) {
                /** @var PurchasableDependency $dependency */
                if (! $this->containsPurchasableOfType($dependency->purchasableClass, $dependency->eventId)) {
                    $errors[] = $dependency->message;
                }
            }
        }

        return array_unique($errors);
    }

    /**
     * Get or create a cart for the given user.
     */
    public static function forUser(User $user): self
    {
        return self::firstOrCreate(['user_id' => $user->id]);
    }
}
