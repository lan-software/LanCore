<?php

namespace App\Domain\Ticketing\Security;

use Illuminate\Support\Carbon;

/**
 * @see docs/mil-std-498/SDD.md §3.3.2
 */
final readonly class IssuedToken
{
    public function __construct(
        public string $qrPayload,
        public string $nonceHash,
        public string $kid,
        public Carbon $issuedAt,
        public Carbon $expiresAt,
    ) {}
}
