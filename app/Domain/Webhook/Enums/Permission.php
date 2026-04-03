<?php

namespace App\Domain\Webhook\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageWebhooks = 'manage_webhooks';
}
