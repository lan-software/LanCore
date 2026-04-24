<?php

namespace App\Domain\Seating\Support;

use App\Domain\Seating\Models\SeatPlan;

/**
 * Pure helper for reasoning about per-block ticket-category restrictions.
 *
 * Each block in a seat plan's JSONB `data` may carry an optional
 * `allowed_ticket_category_ids: number[]`. An empty/missing list is treated as
 * open to all categories (permissive default — keeps existing plans working
 * without retroactive edits).
 *
 * @see docs/mil-std-498/SSS.md CAP-SET-005
 * @see docs/mil-std-498/SRS.md SET-F-011
 */
class SeatingCategoryRules
{
    /**
     * Does the identified block accept a ticket belonging to the given category?
     * Unknown blocks fall back to `true` because enforcement must happen elsewhere
     * — we don't punish callers for giving us a stale block id.
     */
    public static function blockAccepts(SeatPlan $plan, string $blockId, ?int $categoryId): bool
    {
        $block = self::findBlockById($plan, $blockId);

        if ($block === null) {
            return true;
        }

        return self::blockAcceptsCategoryList($block, $categoryId);
    }

    /**
     * Find the block whose seats contain the given seat id.
     *
     * @return array<string, mixed>|null
     */
    public static function findBlockForSeat(SeatPlan $plan, string $seatId): ?array
    {
        foreach (($plan->data['blocks'] ?? []) as $block) {
            foreach (($block['seats'] ?? []) as $seat) {
                if ((string) ($seat['id'] ?? '') === $seatId) {
                    return $block;
                }
            }
        }

        return null;
    }

    /**
     * Normalise the allowed-category list of a block. Missing/invalid ⇒ empty
     * (i.e. open to all).
     *
     * @param  array<string, mixed>  $block
     * @return array<int, int>
     */
    public static function allowedCategoryIds(array $block): array
    {
        $raw = $block['allowed_ticket_category_ids'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($id): ?int => is_numeric($id) ? (int) $id : null,
            $raw,
        ), fn (?int $id): bool => $id !== null));
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private static function blockAcceptsCategoryList(array $block, ?int $categoryId): bool
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
     * @return array<string, mixed>|null
     */
    private static function findBlockById(SeatPlan $plan, string $blockId): ?array
    {
        foreach (($plan->data['blocks'] ?? []) as $block) {
            if ((string) ($block['id'] ?? '') === $blockId) {
                return $block;
            }
        }

        return null;
    }
}
