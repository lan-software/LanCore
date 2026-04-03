<?php

namespace App\Domain\Sponsoring\Actions;

use App\Domain\Sponsoring\Models\SponsorLevel;

/**
 * @see docs/mil-std-498/SSS.md CAP-SPO-002
 * @see docs/mil-std-498/SRS.md SPO-F-002
 */
class UpdateSponsorLevel
{
    /**
     * @param  array{name?: string, color?: string|null, sort_order?: int}  $attributes
     */
    public function execute(SponsorLevel $sponsorLevel, array $attributes): void
    {
        $sponsorLevel->fill($attributes)->save();
    }
}
