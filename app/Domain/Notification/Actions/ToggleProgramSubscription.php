<?php

namespace App\Domain\Notification\Actions;

use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Program\Models\Program;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md NTF-F-004
 */
class ToggleProgramSubscription
{
    public function execute(User $user, Program $program): bool
    {
        $subscription = ProgramNotificationSubscription::query()
            ->where('user_id', $user->id)
            ->where('program_id', $program->id)
            ->first();

        if ($subscription) {
            $subscription->delete();

            return false;
        }

        ProgramNotificationSubscription::create([
            'user_id' => $user->id,
            'program_id' => $program->id,
        ]);

        return true;
    }
}
