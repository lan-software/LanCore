<?php

namespace App\Domain\DataLifecycle\DTOs;

use Carbon\CarbonInterface;

final readonly class AnonymizationResult
{
    /**
     * @param  array<string, mixed>  $summary  Short, JSON-encodable diagnostics for the audit log.
     */
    public function __construct(
        public int $recordsScrubbed,
        public int $recordsKeptUnderRetention,
        public ?CarbonInterface $retentionUntil,
        public array $summary = [],
    ) {}

    public static function nothingToDo(): self
    {
        return new self(0, 0, null);
    }
}
