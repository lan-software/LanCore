<?php

namespace App\Domain\Competition\Enums;

use App\Contracts\PermissionEnum;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-002
 */
enum Permission: string implements PermissionEnum
{
    case ManageCompetitions = 'manage_competitions';
}
