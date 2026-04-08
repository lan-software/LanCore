<?php

namespace App\Domain\Ticketing\Security;

/**
 * @see docs/mil-std-498/SDD.md §3.3.2
 */
final readonly class TokenVerification
{
    public function __construct(
        public int $tid,
        public string $nonce,
        public int $iat,
        public int $exp,
        public ?int $evt,
        public string $kid,
    ) {}
}
