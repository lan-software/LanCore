<?php

namespace App\Domain\Shop\Contracts;

interface Purchasable
{
    /**
     * Get the morph class name for this purchasable.
     */
    public function getPurchasableType(): string;

    /**
     * Get the unique identifier for this purchasable.
     */
    public function getPurchasableId(): int;

    /**
     * Get the display title for this purchasable.
     */
    public function getTitle(): string;

    /**
     * Get the display description for this purchasable.
     */
    public function getDescription(): ?string;

    /**
     * Get the unit price in cents.
     */
    public function getUnitPrice(): int;

    /**
     * Whether this item is currently available for purchase.
     */
    public function isAvailableForPurchase(): bool;

    /**
     * Get the maximum quantity a single user may purchase.
     */
    public function getMaxQuantity(): int;

    /**
     * Build a line-item payload for checkout.
     *
     * @return array{name: string, description: string, currency: string, unit_amount: int, quantity: int}
     */
    public function toLineItemData(int $quantity = 1): array;

    /**
     * Get the purchasable dependencies that must exist in the cart or on the user's account.
     * Returns an array of dependency descriptors.
     *
     * @return array<int, PurchasableDependency>
     */
    public function getPurchasableDependencies(): array;
}
