<?php

namespace App\Domain\DataLifecycle\Enums;

use App\Domain\DataLifecycle\Models\DeletionRequest;

/**
 * State machine for {@see DeletionRequest}.
 *
 * Allowed transitions:
 *   PendingEmailConfirm → PendingGrace (user clicked confirmation link)
 *   PendingEmailConfirm → Cancelled (user/admin cancelled before confirm)
 *   PendingGrace        → Anonymized (grace expired, scheduler ran)
 *   PendingGrace        → Cancelled (user/admin cancelled during grace)
 *   *                   → ForceDeleted (admin Force-Delete bypass; terminal)
 *
 * Anonymized, Cancelled, ForceDeleted are terminal.
 */
enum DeletionRequestStatus: string
{
    case PendingEmailConfirm = 'pending_email_confirm';
    case PendingGrace = 'pending_grace';
    case Anonymized = 'anonymized';
    case Cancelled = 'cancelled';
    case ForceDeleted = 'force_deleted';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Anonymized, self::Cancelled, self::ForceDeleted => true,
            default => false,
        };
    }

    public function isCancellable(): bool
    {
        return match ($this) {
            self::PendingEmailConfirm, self::PendingGrace => true,
            default => false,
        };
    }
}
