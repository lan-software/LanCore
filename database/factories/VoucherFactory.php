<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\VoucherType;
use App\Domain\Shop\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'type' => VoucherType::Percentage,
            'discount_amount' => null,
            'discount_percent' => fake()->numberBetween(5, 50),
            'max_uses' => fake()->optional()->numberBetween(1, 100),
            'times_used' => 0,
            'valid_from' => now(),
            'valid_until' => now()->addMonths(3),
            'is_active' => true,
            'event_id' => null,
        ];
    }

    public function fixedAmount(int $amount = 1000): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => VoucherType::FixedAmount,
            'discount_amount' => $amount,
            'discount_percent' => null,
        ]);
    }

    public function forEvent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_id' => Event::factory(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'valid_until' => now()->subDay(),
        ]);
    }
}
