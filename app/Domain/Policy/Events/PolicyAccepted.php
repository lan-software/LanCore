<?php

namespace App\Domain\Policy\Events;

use App\Domain\Policy\Models\PolicyAcceptance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-004
 * @see docs/mil-std-498/SRS.md POL-F-010, POL-F-012
 */
class PolicyAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PolicyAcceptance $acceptance,
    ) {}
}
