<?php

namespace App\Domain\EmailLog\Policies;

use App\Domain\EmailLog\Enums\Permission;
use App\Domain\EmailLog\Models\EmailMessage;
use App\Models\User;

class EmailMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewEmailLog);
    }

    public function view(User $user, EmailMessage $message): bool
    {
        return $user->hasPermission(Permission::ViewEmailLog);
    }

    public function resend(User $user, EmailMessage $message): bool
    {
        return $user->hasPermission(Permission::ResendEmail);
    }
}
