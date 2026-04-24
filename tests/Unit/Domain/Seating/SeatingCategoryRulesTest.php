<?php

use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Support\SeatingCategoryRules;
use App\Domain\Ticketing\Models\TicketCategory;

function buildBlockWithRestrictions(array $categoryIds): SeatPlanBlock
{
    $plan = SeatPlan::factory()->empty()->create();
    $block = SeatPlanBlock::factory()->create(['seat_plan_id' => $plan->id]);

    if ($categoryIds !== []) {
        $block->categoryRestrictions()->sync($categoryIds);
    }

    return $block->fresh(['categoryRestrictions']);
}

it('accepts any category when the block has no allowlist (permissive default)', function (): void {
    $block = buildBlockWithRestrictions([]);

    expect(SeatingCategoryRules::blockAccepts($block, null))->toBeTrue()
        ->and(SeatingCategoryRules::blockAccepts($block, 1))->toBeTrue()
        ->and(SeatingCategoryRules::blockAccepts($block, 99))->toBeTrue();
});

it('accepts only listed categories when the block has a non-empty allowlist', function (): void {
    $plan = SeatPlan::factory()->empty()->create();
    $categoryA = TicketCategory::factory()->create(['event_id' => $plan->event_id]);
    $categoryB = TicketCategory::factory()->create(['event_id' => $plan->event_id]);
    $categoryOther = TicketCategory::factory()->create(['event_id' => $plan->event_id]);

    $block = SeatPlanBlock::factory()->create(['seat_plan_id' => $plan->id]);
    $block->categoryRestrictions()->sync([$categoryA->id, $categoryB->id]);
    $block->refresh()->load('categoryRestrictions');

    expect(SeatingCategoryRules::blockAccepts($block, $categoryA->id))->toBeTrue()
        ->and(SeatingCategoryRules::blockAccepts($block, $categoryB->id))->toBeTrue()
        ->and(SeatingCategoryRules::blockAccepts($block, $categoryOther->id))->toBeFalse()
        ->and(SeatingCategoryRules::blockAccepts($block, null))->toBeFalse();
});

it('allowedCategoryIds returns the pivot ids as integers', function (): void {
    $plan = SeatPlan::factory()->empty()->create();
    $categoryA = TicketCategory::factory()->create(['event_id' => $plan->event_id]);
    $categoryB = TicketCategory::factory()->create(['event_id' => $plan->event_id]);

    $block = SeatPlanBlock::factory()->create(['seat_plan_id' => $plan->id]);
    $block->categoryRestrictions()->sync([$categoryA->id, $categoryB->id]);
    $block->load('categoryRestrictions');

    expect(SeatingCategoryRules::allowedCategoryIds($block))->toEqualCanonicalizing([$categoryA->id, $categoryB->id]);
});

it('allowedCategoryIds returns an empty array when no restrictions exist', function (): void {
    $block = buildBlockWithRestrictions([]);

    expect(SeatingCategoryRules::allowedCategoryIds($block))->toBe([]);
});
