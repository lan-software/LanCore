<?php

namespace App\Domain\Competition\Enums;

/**
 * @see docs/mil-std-498/SRS.md COM-F-MATCH-FINAL-001
 */
enum MatchFinalizationSource: string
{
    case SubmittedByParticipants = 'submitted_by_participants';
    case ForcedByAdmin = 'forced_by_admin';
}
