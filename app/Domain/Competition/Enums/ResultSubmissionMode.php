<?php

namespace App\Domain\Competition\Enums;

enum ResultSubmissionMode: string
{
    case AdminOnly = 'admin_only';
    case ParticipantsWithProof = 'participants_with_proof';
}
