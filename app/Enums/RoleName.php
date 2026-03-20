<?php

namespace App\Enums;

enum RoleName: string
{
    case User = 'user';
    case Admin = 'admin';
    case Superadmin = 'superadmin';
    case SponsorManager = 'sponsor_manager';
}
