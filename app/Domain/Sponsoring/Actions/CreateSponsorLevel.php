<?php

namespace App\Domain\Sponsoring\Actions;

use App\Domain\Sponsoring\Models\SponsorLevel;

/**
 * @see docs/mil-std-498/SSS.md CAP-SPO-002
 * @see docs/mil-std-498/SRS.md SPO-F-002
 */
class CreateSponsorLevel
{
    /**
     * @param  array{name: string, color?: string|null, sort_order?: int}  $attributes
     */
    public function execute(array $attributes): SponsorLevel
    {
        if (! isset($attributes['sort_order'])) {
            $attributes['sort_order'] = (SponsorLevel::max('sort_order') ?? -1) + 1;
        }

        return SponsorLevel::create($attributes);
    }
}
