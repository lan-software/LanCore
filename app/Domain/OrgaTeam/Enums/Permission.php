<?php

namespace App\Domain\OrgaTeam\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageOrgaTeams = 'manage_orga_teams';
}
