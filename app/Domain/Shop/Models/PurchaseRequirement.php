<?php

namespace App\Domain\Shop\Models;

use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketType;
use Database\Factories\PurchaseRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable([
    'name', 'description', 'requirements_content', 'acknowledgements', 'is_active', 'requires_scroll',
])]
class PurchaseRequirement extends Model
{
    /** @use HasFactory<PurchaseRequirementFactory> */
    use HasFactory;

    protected static function newFactory(): PurchaseRequirementFactory
    {
        return PurchaseRequirementFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledgements' => 'array',
            'is_active' => 'boolean',
            'requires_scroll' => 'boolean',
        ];
    }

    public function ticketTypes(): MorphToMany
    {
        return $this->morphedByMany(TicketType::class, 'purchasable', 'purchase_requirement_purchasable');
    }

    public function addons(): MorphToMany
    {
        return $this->morphedByMany(Addon::class, 'purchasable', 'purchase_requirement_purchasable');
    }
}
