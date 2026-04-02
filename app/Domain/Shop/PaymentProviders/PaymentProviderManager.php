<?php

namespace App\Domain\Shop\PaymentProviders;

use App\Domain\Shop\Contracts\PaymentProvider;
use App\Domain\Shop\Enums\PaymentMethod;
use InvalidArgumentException;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-002, CAP-SHP-003
 * @see docs/mil-std-498/SRS.md SHP-F-005
 */
class PaymentProviderManager
{
    /** @var array<string, PaymentProvider> */
    private array $providers = [];

    public function register(PaymentProvider $provider): void
    {
        $this->providers[$provider->method()->value] = $provider;
    }

    public function resolve(PaymentMethod $method): PaymentProvider
    {
        $provider = $this->providers[$method->value] ?? null;

        if (! $provider) {
            throw new InvalidArgumentException("Payment provider '{$method->value}' is not registered.");
        }

        return $provider;
    }

    /**
     * Get all registered payment methods with their labels.
     *
     * @return array<int, array{value: string, label: string, requires_redirect: bool}>
     */
    public function availableMethods(): array
    {
        return array_values(array_map(
            fn (PaymentProvider $provider): array => [
                'value' => $provider->method()->value,
                'label' => $provider->method()->label(),
                'requires_redirect' => $provider->requiresRedirect(),
            ],
            $this->providers,
        ));
    }
}
