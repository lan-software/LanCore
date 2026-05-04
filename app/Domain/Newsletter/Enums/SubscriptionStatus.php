<?php

namespace App\Domain\Newsletter\Enums;

/**
 * Mirrors Listmonk's subscriber-status field on a per-list basis.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-002
 */
enum SubscriptionStatus: string
{
    case Enabled = 'enabled';
    case Unsubscribed = 'unsubscribed';
    case Blocklisted = 'blocklisted';
}
