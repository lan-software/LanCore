<?php

namespace App\Domain\Sponsoring\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageSponsors = 'manage_sponsors';
    case ManageSponsorLevels = 'manage_sponsor_levels';
    case ManageAssignedSponsors = 'manage_assigned_sponsors';
}
