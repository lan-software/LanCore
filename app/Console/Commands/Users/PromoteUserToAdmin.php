<?php

namespace App\Console\Commands\Users;

use App\Actions\User\ChangeRoles;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:promote {email : The email address of the user to promote} {--role=admin : The role to assign (admin or superadmin)}')]
#[Description('Promote a user to admin or superadmin')]
class PromoteUserToAdmin extends Command
{
    public function __construct(private readonly ChangeRoles $changeRoles)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = $this->argument('email');
        $roleName = RoleName::tryFrom($this->option('role'));

        if (! $roleName || $roleName === RoleName::User) {
            $this->error("Invalid role. Use 'admin' or 'superadmin'.");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");

            return self::FAILURE;
        }

        if ($user->hasRole($roleName)) {
            $this->info("{$user->name} ({$email}) already has the '{$roleName->value}' role.");

            return self::SUCCESS;
        }

        $this->changeRoles->assign($user, $roleName);

        $this->info("Promoted {$user->name} ({$email}) to '{$roleName->value}'.");

        return self::SUCCESS;
    }
}
