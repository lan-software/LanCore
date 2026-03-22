<?php

namespace App\Domain\Sponsoring\Models;

use App\Concerns\HasModelCache;
use Database\Factories\SponsorLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'color', 'sort_order'])]
class SponsorLevel extends Model
{
    /** @use HasFactory<SponsorLevelFactory> */
    use HasFactory, HasModelCache;

    /**
     * @return array<int, string>
     */
    protected static function dropdownColumns(): array
    {
        return ['id', 'name', 'color'];
    }

    /**
     * @return Builder<static>
     */
    protected static function dropdownQuery(): Builder
    {
        return static::query()->orderBy('sort_order');
    }

    protected static function newFactory(): SponsorLevelFactory
    {
        return SponsorLevelFactory::new();
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }
}
