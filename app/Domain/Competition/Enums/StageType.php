<?php

namespace App\Domain\Competition\Enums;

enum StageType: string
{
    case GroupStage = 'group_stage';
    case SingleElimination = 'single_elimination';
    case DoubleElimination = 'double_elimination';
    case Swiss = 'swiss';
    case RoundRobin = 'round_robin';
    case RaceHeat = 'race_heat';
    case FinalStage = 'final_stage';
}
