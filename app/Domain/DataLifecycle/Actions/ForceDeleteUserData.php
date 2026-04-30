<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Events\UserForceDeleted;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Bypass retention windows and hard-delete the user and all of their data
 * that the per-domain RetentionPolicy permits via `can_be_force_deleted`.
 * Pinned policies still hold; the action will refuse if any anonymizer
 * raises during the purge run.
 *
 * The call is fully audited: the request row is updated, owen-it captures
 * the change, and {@see UserForceDeleted} fires after the user row is gone.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-006
 * @see docs/mil-std-498/SRS.md DL-F-015
 */
class ForceDeleteUserData
{
    public function __construct(
        private AnonymizeUser $anonymizeUser,
        private RequestUserDeletion $requestUserDeletion,
    ) {}

    public function execute(User $subject, User $admin, string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required for ForceDeleteUserData.');
        }

        DB::transaction(function () use ($subject, $admin, $reason): void {
            $request = DeletionRequest::query()
                ->where('user_id', $subject->getKey())
                ->whereIn('status', [
                    DeletionRequestStatus::PendingEmailConfirm->value,
                    DeletionRequestStatus::PendingGrace->value,
                    DeletionRequestStatus::Anonymized->value,
                ])
                ->latest('id')
                ->first();

            if ($request === null) {
                $request = DeletionRequest::create([
                    'user_id' => $subject->getKey(),
                    'initiator' => DeletionInitiator::Admin,
                    'requested_by_admin_id' => $admin->getKey(),
                    'status' => DeletionRequestStatus::PendingGrace,
                    'reason' => $reason,
                    'metadata' => ['source' => 'force_delete_user_data'],
                ]);
            }

            $this->anonymizeUser->execute($request->refresh(), AnonymizationMode::PurgeNow);

            $userId = $subject->getKey();

            $deleted = DB::table('users')->where('id', $userId)->delete();

            if ($deleted === 0) {
                throw new RuntimeException('Force-delete failed: users row was not removed.');
            }

            $request->refresh()->update([
                'status' => DeletionRequestStatus::ForceDeleted,
                'force_deleted_at' => now(),
                'reason' => $reason,
                'metadata' => array_merge($request->metadata ?? [], [
                    'force_deleted_by_admin_id' => $admin->getKey(),
                    'force_delete_reason' => $reason,
                ]),
            ]);

            UserForceDeleted::dispatch($userId, $reason);
        });
    }
}
