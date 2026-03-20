<?php

namespace App\Domain\Sponsoring\Models;

use Database\Factories\SponsorLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'color', 'sort_order'])]
class SponsorLevel extends Model
{
    /** @use HasFactory<SponsorLevelFactory> */
    use HasFactory;

    protected static function newFactory(): SponsorLevelFactory
    {
        return SponsorLevelFactory::new();
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }
}
