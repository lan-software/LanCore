<?php

namespace App\Domain\Achievements\Actions;

use App\Domain\Achievements\Models\Achievement;

class DeleteAchievement
{
    public function execute(Achievement $achievement): void
    {
        $achievement->delete();
    }
}
