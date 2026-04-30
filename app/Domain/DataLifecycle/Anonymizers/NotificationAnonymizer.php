<?php

namespace App\Domain\DataLifecycle\Anonymizers;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\DTOs\AnonymizationResult;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hard-deletes push subscriptions (carry endpoint URLs with auth tokens) and
 * notification preferences. None of these have a retention obligation.
 */
final class NotificationAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::NotificationsPreference;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        $deleted = 0;

        foreach (['push_subscriptions', 'program_notification_subscriptions', 'notification_preferences'] as $table) {
            if (Schema::hasTable($table)) {
                $deleted += DB::table($table)->where('user_id', $user->getKey())->delete();
            }
        }

        return new AnonymizationResult(
            recordsScrubbed: $deleted,
            recordsKeptUnderRetention: 0,
            retentionUntil: null,
        );
    }
}
