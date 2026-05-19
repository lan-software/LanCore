<?php

namespace App\Domain\CompetitionSchedule\Policies;

use App\Domain\Competition\Enums\Permission;
use App\Domain\CompetitionSchedule\Models\CompetitionRoundSchedule;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md COMP-RND-005
 */
class CompetitionRoundSchedulePolicy
{
    public function update(User $user, CompetitionRoundSchedule $schedule): bool
    {
        return $user->hasPermission(Permission::ManageCompetitions);
    }
}
