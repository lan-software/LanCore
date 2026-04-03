<?php

namespace App\Domain\Games\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageGames = 'manage_games';
}
