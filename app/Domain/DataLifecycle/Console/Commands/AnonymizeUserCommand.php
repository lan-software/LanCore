<?php

namespace App\Domain\DataLifecycle\Console\Commands;

use App\Domain\DataLifecycle\Actions\AnonymizeUser;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lifecycle:user:anonymize {email}')]
#[Description('Anonymize a user that already has an active deletion request (skips the remaining grace).')]
class AnonymizeUserCommand extends Command
{
    public function handle(AnonymizeUser $action): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found for {$email}.");

            return self::FAILURE;
        }

        $request = DeletionRequest::query()
            ->where('user_id', $user->getKey())
            ->whereIn('status', [
                DeletionRequestStatus::PendingEmailConfirm->value,
                DeletionRequestStatus::PendingGrace->value,
            ])
            ->latest('id')
            ->first();

        if ($request === null) {
            $this->error('No active deletion request for this user. Run lifecycle:user:delete first.');

            return self::FAILURE;
        }

        $action->execute($request);

        $this->info("User #{$user->id} has been anonymized.");

        return self::SUCCESS;
    }
}
