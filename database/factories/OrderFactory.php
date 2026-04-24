<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Support\CurrencyResolver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1500, 20000);

        return [
            'payment_method' => PaymentMethod::Stripe,
            'provider_session_id' => null,
            'provider_transaction_id' => null,
            'status' => OrderStatus::Completed,
            'subtotal' => $subtotal,
            'discount' => 0,
            'total' => $subtotal,
            'currency' => CurrencyResolver::code(),
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'voucher_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Pending,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Failed,
        ]);
    }

    public function onSite(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => PaymentMethod::OnSite,
        ]);
    }
}
