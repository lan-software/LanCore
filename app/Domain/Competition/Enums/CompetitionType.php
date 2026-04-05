<?php

namespace App\Domain\Competition\Enums;

enum CompetitionType: string
{
    case Tournament = 'tournament';
    case League = 'league';
    case Race = 'race';
}
