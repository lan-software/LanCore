<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Events\UserDeletionRequested;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Open a deletion request for a user. Used by both the user self-service
 * flow and the admin-induced flow. Generates a one-shot HMAC token that the
 * user must present (via email link) to confirm the request.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-001, CAP-DL-002
 * @see docs/mil-std-498/SRS.md DL-F-001, DL-F-002
 */
class RequestUserDeletion
{
    /**
     * @return array{request: DeletionRequest, plainToken: string}
     */
    public function execute(
        User $subject,
        DeletionInitiator $initiator,
        ?string $reason = null,
        ?User $requestedByAdmin = null,
    ): array {
        if ($subject->isAnonymized()) {
            throw new RuntimeException('User is already anonymized; cannot request deletion again.');
        }

        return DB::transaction(function () use ($subject, $initiator, $reason, $requestedByAdmin): array {
            $existing = DeletionRequest::query()
                ->where('user_id', $subject->getKey())
                ->whereIn('status', [
                    DeletionRequestStatus::PendingEmailConfirm->value,
                    DeletionRequestStatus::PendingGrace->value,
                ])
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw new RuntimeException('A pending deletion request already exists for this user.');
            }

            $plainToken = Str::random(48);

            $request = DeletionRequest::create([
                'user_id' => $subject->getKey(),
                'initiator' => $initiator,
                'requested_by_user_id' => $initiator === DeletionInitiator::User ? $subject->getKey() : null,
                'requested_by_admin_id' => $requestedByAdmin?->getKey(),
                'status' => DeletionRequestStatus::PendingEmailConfirm,
                'reason' => $reason,
                'email_confirmation_token' => hash('sha256', $plainToken),
                'metadata' => [],
            ]);

            DB::table('users')
                ->where('id', $subject->getKey())
                ->update(['pending_deletion_at' => now()]);

            UserDeletionRequested::dispatch($request, $plainToken);

            return ['request' => $request->refresh(), 'plainToken' => $plainToken];
        });
    }
}
