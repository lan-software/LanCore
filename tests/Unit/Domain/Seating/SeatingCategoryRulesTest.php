<?php

use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Support\SeatingCategoryRules;

function makePlan(array $blocks): SeatPlan
{
    return SeatPlan::factory()->create(['data' => ['blocks' => $blocks]]);
}

it('accepts any category when the block has no allowlist (permissive default)', function (): void {
    $plan = makePlan([
        ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
            ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
        ]],
    ]);

    expect(SeatingCategoryRules::blockAccepts($plan, 'a', null))->toBeTrue()
        ->and(SeatingCategoryRules::blockAccepts($plan, 'a', 1))->toBeTrue()
        ->and(SeatingCategoryRules::blockAccepts($plan, 'a', 99))->toBeTrue();
});

it('accepts only listed categories when the block has a non-empty allowlist', function (): void {
    $plan = makePlan([
        ['id' => 'vip', 'title' => 'VIP', 'color' => '#fff', 'seats' => [], 'allowed_ticket_category_ids' => [7, 9]],
    ]);

    expect(SeatingCategoryRules::blockAccepts($plan, 'vip', 7))->toBeTrue()
        ->and(SeatingCategoryRules::blockAccepts($plan, 'vip', 9))->toBeTrue()
        ->and(SeatingCategoryRules::blockAccepts($plan, 'vip', 1))->toBeFalse()
        ->and(SeatingCategoryRules::blockAccepts($plan, 'vip', null))->toBeFalse();
});

it('treats an unknown block id as permissive (enforcement is elsewhere)', function (): void {
    $plan = makePlan([
        ['id' => 'vip', 'title' => 'VIP', 'color' => '#fff', 'seats' => [], 'allowed_ticket_category_ids' => [7]],
    ]);

    expect(SeatingCategoryRules::blockAccepts($plan, 'unknown', 1))->toBeTrue();
});

it('findBlockForSeat returns the containing block or null', function (): void {
    $plan = makePlan([
        ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
            ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
        ]],
        ['id' => 'b', 'title' => 'B', 'color' => '#fff', 'seats' => [
            ['id' => 'B1', 'title' => 'B1', 'x' => 0, 'y' => 0, 'salable' => true],
        ]],
    ]);

    $block = SeatingCategoryRules::findBlockForSeat($plan, 'B1');
    expect($block)->not->toBeNull()
        ->and($block['id'])->toBe('b')
        ->and(SeatingCategoryRules::findBlockForSeat($plan, 'NONE'))->toBeNull();
});

it('allowedCategoryIds normalises missing and invalid values to an empty list', function (): void {
    expect(SeatingCategoryRules::allowedCategoryIds([]))->toBe([])
        ->and(SeatingCategoryRules::allowedCategoryIds(['allowed_ticket_category_ids' => null]))->toBe([])
        ->and(SeatingCategoryRules::allowedCategoryIds(['allowed_ticket_category_ids' => 'not-an-array']))->toBe([])
        ->and(SeatingCategoryRules::allowedCategoryIds(['allowed_ticket_category_ids' => [1, '2', 'x']]))->toBe([1, 2]);
});
