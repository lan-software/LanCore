<?php

namespace App\Domain\Seating\Support;

use App\Domain\Seating\Models\SeatPlanBlock;
use Illuminate\Support\Collection;

/**
 * Per-block ticket-category restrictions (SET-F-011).
 *
 * Restrictions live in `seat_plan_block_category_restrictions`. An empty pivot
 * for a block is treated as accepting every category (permissive default).
 *
 * @see docs/mil-std-498/SSS.md CAP-SET-005
 * @see docs/mil-std-498/SRS.md SET-F-011
 */
class SeatingCategoryRules
{
    /**
     * Does the block accept a ticket belonging to the given category?
     * Expects the pivot to be eager-loaded via `with('categoryRestrictions')`
     * to avoid N+1.
     */
    public static function blockAccepts(SeatPlanBlock $block, ?int $categoryId): bool
    {
        $allowed = self::allowedCategoryIds($block);

        if ($allowed === []) {
            return true;
        }

        if ($categoryId === null) {
            return false;
        }

        return in_array($categoryId, $allowed, true);
    }

    /**
     * @return array<int, int>
     */
    public static function allowedCategoryIds(SeatPlanBlock $block): array
    {
        /** @var Collection<int, mixed> $restrictions */
        $restrictions = $block->relationLoaded('categoryRestrictions')
            ? $block->getRelation('categoryRestrictions')
            : $block->categoryRestrictions()->get();

        return $restrictions->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
    }
}
