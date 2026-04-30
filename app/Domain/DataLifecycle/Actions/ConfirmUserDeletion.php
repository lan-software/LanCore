<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Events\UserDeletionConfirmed;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Validate the email-confirmation token, advance the request into the
 * 30-day grace window, and schedule its anonymization.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-003
 * @see docs/mil-std-498/SRS.md DL-F-003
 */
class ConfirmUserDeletion
{
    public function __construct(private int $graceDays = 30) {}

    public function execute(string $plainToken): DeletionRequest
    {
        $hashed = hash('sha256', $plainToken);

        return DB::transaction(function () use ($hashed) {
            $request = DeletionRequest::query()
                ->where('email_confirmation_token', $hashed)
                ->lockForUpdate()
                ->first();

            if ($request === null) {
                throw new RuntimeException('Invalid or expired confirmation token.');
            }

            if ($request->status !== DeletionRequestStatus::PendingEmailConfirm) {
                throw new RuntimeException('Deletion request is no longer pending email confirmation.');
            }

            $now = now();

            $request->update([
                'status' => DeletionRequestStatus::PendingGrace,
                'email_confirmed_at' => $now,
                'scheduled_for' => $now->copy()->addDays($this->graceDays),
                'email_confirmation_token' => null,
            ]);

            UserDeletionConfirmed::dispatch($request);

            return $request->refresh();
        });
    }
}
