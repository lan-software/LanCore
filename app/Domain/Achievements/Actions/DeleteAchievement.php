<?php

namespace App\Domain\Achievements\Actions;

use App\Domain\Achievements\Models\Achievement;

/**
 * @see docs/mil-std-498/SRS.md ACH-F-001
 */
class DeleteAchievement
{
    public function execute(Achievement $achievement): void
    {
        $achievement->delete();
    }
}
