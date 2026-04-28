<?php

namespace App\Domain\Policy\Events;

use App\Domain\Policy\Models\PolicyVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by PublishPolicyVersion. When `silent` is true the editorial publish
 * does not require user action — listeners that mass-email or trigger gates
 * must check `! $silent` before reacting.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-003, CAP-POL-008
 * @see docs/mil-std-498/SRS.md POL-F-008, POL-F-009
 */
class PolicyVersionPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PolicyVersion $version,
        public readonly bool $isNonEditorial,
        public readonly bool $silent,
    ) {}
}
