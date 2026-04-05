<?php

namespace App\Domain\Announcement\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageAnnouncements = 'manage_announcements';
}
