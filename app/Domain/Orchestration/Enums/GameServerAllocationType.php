<?php

namespace App\Domain\Orchestration\Enums;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-004
 */
enum GameServerAllocationType: string
{
    case Competition = 'competition';
    case Casual = 'casual';
    case Flexible = 'flexible';

    /**
     * Server selection priority (lower = higher priority).
     */
    public function priority(): int
    {
        return match ($this) {
            self::Competition => 1,
            self::Flexible => 2,
            self::Casual => 3,
        };
    }
}
