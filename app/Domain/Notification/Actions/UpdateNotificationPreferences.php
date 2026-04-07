<?php

namespace App\Domain\Notification\Actions;

use App\Domain\Notification\Events\NotificationPreferencesUpdated;
use App\Domain\Notification\Models\NotificationPreference;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md NTF-F-001, NTF-F-002
 */
class UpdateNotificationPreferences
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, array $attributes): NotificationPreference
    {
        $preferences = NotificationPreference::firstOrCreate(['user_id' => $user->id]);

        $preferences->update($attributes);

        NotificationPreferencesUpdated::dispatch($user);

        return $preferences;
    }
}
