<?php

namespace App\Domain\Sponsoring\Actions;

use App\Domain\Sponsoring\Models\SponsorLevel;

/**
 * @see docs/mil-std-498/SRS.md SPO-F-002
 */
class DeleteSponsorLevel
{
    public function execute(SponsorLevel $sponsorLevel): void
    {
        $sponsorLevel->delete();
    }
}
