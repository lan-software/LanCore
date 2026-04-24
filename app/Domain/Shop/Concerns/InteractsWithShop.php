<?php

namespace App\Domain\Shop\Concerns;

use App\Domain\Shop\Contracts\PurchasableDependency;
use App\Domain\Shop\Support\CurrencyResolver;

/**
 * Provides default Purchasable implementations for Eloquent models.
 *
 * Expects the model to have: name, description, price columns.
 */
trait InteractsWithShop
{
    public function getPurchasableType(): string
    {
        return static::class;
    }

    public function getPurchasableId(): int
    {
        return $this->getKey();
    }

    public function getTitle(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getUnitPrice(): int
    {
        return $this->price;
    }

    /**
     * Build a line-item payload for checkout.
     *
     * @return array{name: string, description: string, currency: string, unit_amount: int, quantity: int}
     */
    public function toLineItemData(int $quantity = 1): array
    {
        return [
            'name' => $this->getTitle(),
            'description' => $this->getDescription() ?? $this->getTitle(),
            'currency' => CurrencyResolver::code(),
            'unit_amount' => $this->getUnitPrice(),
            'quantity' => $quantity,
        ];
    }

    /**
     * Format a price in cents to a human-readable currency string.
     */
    public function formattedPrice(): string
    {
        return CurrencyResolver::formatCents($this->getUnitPrice());
    }

    /**
     * @return array<int, PurchasableDependency>
     */
    public function getPurchasableDependencies(): array
    {
        return [];
    }
}
