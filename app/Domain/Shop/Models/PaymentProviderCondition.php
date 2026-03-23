<?php

namespace App\Domain\Shop\Models;

use App\Domain\Shop\Enums\PaymentMethod;
use Database\Factories\PaymentProviderConditionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'payment_method', 'name', 'description', 'content', 'acknowledgement_label',
    'is_required', 'is_active', 'requires_scroll', 'sort_order',
])]
class PaymentProviderCondition extends Model
{
    /** @use HasFactory<PaymentProviderConditionFactory> */
    use HasFactory;

    protected static function newFactory(): PaymentProviderConditionFactory
    {
        return PaymentProviderConditionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'requires_scroll' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function activeForMethod(PaymentMethod $method): Builder
    {
        return static::query()
            ->where('payment_method', $method)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }
}
