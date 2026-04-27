<?php

namespace App\Domain\OrgaTeam\Enums;

enum SubTeamRole: string
{
    case Deputy = 'deputy';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Deputy => __('orga-team.role.fallback_leader'),
            self::Member => __('orga-team.role.member'),
        };
    }
}
