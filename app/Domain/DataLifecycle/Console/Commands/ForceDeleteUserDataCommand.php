<?php

namespace App\Domain\DataLifecycle\Console\Commands;

use App\Domain\DataLifecycle\Actions\ForceDeleteUserData;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

#[Signature('lifecycle:user:force-delete {email} {--reason=} {--admin-id=}')]
#[Description('Bypass retention windows and hard-delete the user. Requires --reason. Use only on signed legal request.')]
class ForceDeleteUserDataCommand extends Command
{
    public function handle(ForceDeleteUserData $action): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->withTrashed()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found for {$email}.");

            return self::FAILURE;
        }

        $reason = $this->option('reason') ?? text(
            label: 'Reason for force-deletion (audited)',
            required: true,
        );

        $adminId = $this->option('admin-id');
        if ($adminId === null) {
            $this->error('--admin-id is required when running force-delete from CLI.');

            return self::FAILURE;
        }

        $admin = User::query()->where('id', $adminId)->first();
        if ($admin === null) {
            $this->error("No admin user found with id {$adminId}.");

            return self::FAILURE;
        }

        if (! $this->option('no-interaction')
            && ! confirm(label: "Force-delete user #{$user->id} ({$user->email}) and bypass retention windows?", default: false)
        ) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $action->execute($user, $admin, $reason);

        $this->warn("User #{$user->id} and force-deletable data have been permanently removed.");

        return self::SUCCESS;
    }
}
