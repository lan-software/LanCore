<?php

namespace App\Domain\Orchestration\Enums;

use App\Contracts\PermissionEnum;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-003
 */
enum Permission: string implements PermissionEnum
{
    case ManageGameServers = 'manage_game_servers';
    case ViewOrchestration = 'view_orchestration';
}
