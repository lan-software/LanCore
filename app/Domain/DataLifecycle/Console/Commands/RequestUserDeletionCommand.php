<?php

namespace App\Domain\DataLifecycle\Console\Commands;

use App\Domain\DataLifecycle\Actions\AnonymizeUser;
use App\Domain\DataLifecycle\Actions\ConfirmUserDeletion;
use App\Domain\DataLifecycle\Actions\RequestUserDeletion;
use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lifecycle:user:delete {email} {--reason=} {--immediate}')]
#[Description('Open a deletion request for a user. With --immediate, skip the email confirmation and grace and anonymize at once.')]
class RequestUserDeletionCommand extends Command
{
    public function handle(
        RequestUserDeletion $request,
        ConfirmUserDeletion $confirm,
        AnonymizeUser $anonymize,
    ): int {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found for {$email}.");

            return self::FAILURE;
        }

        $result = $request->execute(
            subject: $user,
            initiator: DeletionInitiator::Admin,
            reason: $this->option('reason'),
        );

        $this->info("Deletion request #{$result['request']->id} created.");

        if ((bool) $this->option('immediate')) {
            $deletionRequest = $confirm->execute($result['plainToken']);
            $anonymize->execute($deletionRequest);
            $this->warn('User has been anonymized immediately (skipped email confirmation + grace).');
        } else {
            $this->info('Confirmation email dispatched to '.$user->email);
        }

        return self::SUCCESS;
    }
}
