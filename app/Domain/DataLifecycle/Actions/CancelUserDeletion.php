<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Events\UserDeletionCancelled;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cancel a deletion request that hasn't yet been anonymized or
 * force-deleted. Clears `users.pending_deletion_at`.
 *
 * @see docs/mil-std-498/SRS.md DL-F-004
 */
class CancelUserDeletion
{
    public function execute(DeletionRequest $request): DeletionRequest
    {
        if (! $request->status->isCancellable()) {
            throw new RuntimeException("Deletion request status {$request->status->value} is not cancellable.");
        }

        return DB::transaction(function () use ($request) {
            $request->update([
                'status' => DeletionRequestStatus::Cancelled,
                'cancelled_at' => now(),
                'email_confirmation_token' => null,
            ]);

            DB::table('users')
                ->where('id', $request->user_id)
                ->update(['pending_deletion_at' => null]);

            UserDeletionCancelled::dispatch($request);

            return $request->refresh();
        });
    }
}
