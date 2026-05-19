<?php

namespace App\Domain\CompetitionSchedule\Policies;

use App\Domain\Competition\Enums\Permission;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use App\Domain\Event\Models\Event;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md COMP-SCH-007
 */
class CompetitionStageSchedulePolicy
{
    public function viewBoard(User $user, Event $event): bool
    {
        return $user->hasPermission(Permission::ManageCompetitions);
    }

    public function update(User $user, CompetitionStageSchedule $schedule): bool
    {
        return $user->hasPermission(Permission::ManageCompetitions);
    }
}
