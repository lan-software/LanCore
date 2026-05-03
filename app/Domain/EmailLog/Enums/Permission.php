<?php

namespace App\Domain\EmailLog\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ViewEmailLog = 'view_email_log';
    case ResendEmail = 'resend_email';
}
