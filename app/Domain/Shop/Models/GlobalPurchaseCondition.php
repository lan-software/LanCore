<?php

namespace App\Domain\Shop\Models;

use Database\Factories\GlobalPurchaseConditionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-007
 * @see docs/mil-std-498/SRS.md SHP-F-010
 */
#[Fillable([
    'name', 'description', 'content', 'acknowledgement_label',
    'is_required', 'is_active', 'requires_scroll', 'sort_order',
])]
class GlobalPurchaseCondition extends Model
{
    /** @use HasFactory<GlobalPurchaseConditionFactory> */
    use HasFactory;

    protected static function newFactory(): GlobalPurchaseConditionFactory
    {
        return GlobalPurchaseConditionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'requires_scroll' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function activeOrdered(): Builder
    {
        return static::query()->where('is_active', true)->orderBy('sort_order');
    }
}
