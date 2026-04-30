<?php

namespace App\Domain\DataLifecycle\Enums;

/**
 * Drives the per-anonymizer decision of whether to honour retention windows or bypass them.
 *
 *  - Anonymize: scrub PII fields but keep records that are under accounting/legal retention.
 *  - PurgeNow:  used by ForceDeleteUserData. Bypass retention; hard-delete every record except
 *               those whose RetentionPolicy.can_be_force_deleted = false.
 */
enum AnonymizationMode: string
{
    case Anonymize = 'anonymize';
    case PurgeNow = 'purge_now';
}
