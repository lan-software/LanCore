<?php

namespace App\Domain\Seating\Support;

use Illuminate\Support\Collection;

/**
 * Value object returned by UpdateSeatPlan. Encodes the two-phase save contract:
 * first call with confirm=false reports invalidations (no DB write), second
 * call with confirm=true actually writes and returns an `idMap` so the
 * client can reconcile client-side `new-*` ids against the persisted PKs.
 *
 * @see docs/mil-std-498/SRS.md SET-F-012, SET-F-013
 */
final class UpdateSeatPlanResult
{
    /**
     * @param  Collection<int, array<string, mixed>>  $invalidations
     * @param  array{blocks: array<string, int>, rows: array<string, int>, seats: array<string, int>, labels: array<string, int>}  $idMap
     */
    private function __construct(
        private readonly bool $needsConfirmation,
        public readonly Collection $invalidations,
        public readonly int $releasedCount,
        public readonly array $idMap,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $invalidations
     */
    public static function pending(Collection $invalidations): self
    {
        return new self(true, $invalidations, 0, self::emptyIdMap());
    }

    /**
     * @param  array{blocks: array<string, int>, rows: array<string, int>, seats: array<string, int>, labels: array<string, int>}  $idMap
     */
    public static function saved(int $releasedCount, array $idMap): self
    {
        /** @var Collection<int, array<string, mixed>> $empty */
        $empty = collect();

        return new self(false, $empty, $releasedCount, $idMap);
    }

    public function needsConfirmation(): bool
    {
        return $this->needsConfirmation;
    }

    /**
     * @return array{blocks: array<string, int>, rows: array<string, int>, seats: array<string, int>, labels: array<string, int>}
     */
    public static function emptyIdMap(): array
    {
        return [
            'blocks' => [],
            'rows' => [],
            'seats' => [],
            'labels' => [],
        ];
    }
}
