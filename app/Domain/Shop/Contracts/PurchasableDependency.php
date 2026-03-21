<?php

namespace App\Domain\Shop\Contracts;

/**
 * Describes a dependency that a purchasable has on another purchasable type.
 */
class PurchasableDependency
{
    /**
     * @param  class-string<Purchasable>  $purchasableClass  The class of the required purchasable.
     * @param  int|null  $eventId  Scope the dependency to the same event.
     * @param  string  $message  User-facing message when the dependency is not met.
     */
    public function __construct(
        public readonly string $purchasableClass,
        public readonly ?int $eventId = null,
        public readonly string $message = 'This item requires another item in your cart or account.',
    ) {}
}
