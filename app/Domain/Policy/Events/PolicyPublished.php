<?php

namespace App\Domain\Policy\Events;

use App\Domain\Policy\Models\Policy;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once per atomic multi-locale publish. Replaces the older per-locale
 * PolicyVersionPublished event so listeners that fan out to users do so once
 * per publish rather than once per locale row.
 *
 * Editorial publishes set `silent = true`; listeners that mass-email or
 * trigger consent gates must check `! $silent` before reacting.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-003, CAP-POL-008
 * @see docs/mil-std-498/SRS.md POL-F-008, POL-F-009
 */
class PolicyPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Policy $policy,
        public readonly int $versionNumber,
        public readonly bool $isNonEditorial,
        public readonly bool $silent,
    ) {}
}
