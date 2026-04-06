<?php

namespace App\Domain\Shop\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /**
     * @return array<string, bool>
     */
    public static function enabledPaymentMethods(): array
    {
        return static::get('enabled_payment_methods', [
            'stripe' => true,
            'on_site' => true,
        ]);
    }

    public static function isPaymentMethodEnabled(string $method): bool
    {
        $methods = static::enabledPaymentMethods();

        return $methods[$method] ?? true;
    }
}
