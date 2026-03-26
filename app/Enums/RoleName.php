<?php

namespace App\Enums;

enum RoleName: string
{
    case User = 'user';
    case Moderator = 'moderator';
    case Admin = 'admin';
    case Superadmin = 'superadmin';
    case SponsorManager = 'sponsor_manager';
}
