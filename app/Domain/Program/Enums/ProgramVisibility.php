<?php

namespace App\Domain\Program\Enums;

enum ProgramVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Private = 'private';
}
