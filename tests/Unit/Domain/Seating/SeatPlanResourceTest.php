<?php

use App\Domain\Seating\Http\Resources\SeatPlanResource;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanLabel;
use App\Domain\Ticketing\Models\TicketCategory;

it('preserves the pre-normalization wire shape with integer ids', function (): void {
    $plan = SeatPlan::factory()->empty()->create();
    $category = TicketCategory::factory()->create(['event_id' => $plan->event_id]);
    $plan = SeatPlan::factory()->empty()->withBlocks([[
        'title' => 'Main',
        'color' => '#2c3e50',
        'allowed_ticket_category_ids' => [$category->id],
        'rows' => [['name' => 'A', 'seats' => [
            ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true, 'note' => 'aisle', 'color' => '#fff'],
            ['number' => 2, 'title' => 'A2', 'x' => 30, 'y' => 0, 'salable' => false],
        ]]],
        'labels' => [['title' => 'Row A', 'x' => -30, 'y' => 0]],
    ]])->create(['event_id' => $plan->event_id]);

    $plan->load(['blocks.seats', 'blocks.labels', 'blocks.categoryRestrictions']);

    $payload = (new SeatPlanResource($plan))->resolve();

    expect($payload)
        ->toHaveKeys(['id', 'name', 'event_id', 'background_image_url', 'blocks'])
        ->and($payload['blocks'])->toHaveCount(1);

    $block = $payload['blocks'][0];
    expect($block)
        ->toHaveKeys(['id', 'title', 'color', 'background_image_url', 'seats', 'labels', 'allowed_ticket_category_ids'])
        ->and($block['seats'])->toHaveCount(2)
        ->and($block['labels'])->toHaveCount(1)
        ->and($block['allowed_ticket_category_ids'])->toBe([$category->id]);

    $seat = $block['seats'][0];
    expect($seat)
        ->toHaveKeys(['id', 'title', 'x', 'y', 'salable', 'color', 'note', 'custom_data'])
        ->and($seat['id'])->toBeInt()
        ->and($seat['salable'])->toBeTrue()
        ->and($seat['note'])->toBe('aisle');

    expect($block['seats'][1]['salable'])->toBeFalse();
});

it('flattens plan-level labels into the first block for library compat', function (): void {
    $plan = SeatPlan::factory()->empty()->withBlocks([[
        'title' => 'Main',
        'color' => '#1f6feb',
        'rows' => [['name' => 'A', 'seats' => [
            ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
        ]]],
        'labels' => [['title' => 'Stage', 'x' => 200, 'y' => 0, 'sort_order' => 0]],
    ]])->create();

    SeatPlanLabel::query()->create([
        'seat_plan_id' => $plan->id,
        'seat_plan_block_id' => null,
        'title' => 'Entrance',
        'x' => -200,
        'y' => 0,
        'sort_order' => 0,
    ]);

    $plan->load(['blocks.seats', 'blocks.labels', 'blocks.categoryRestrictions', 'globalLabels']);

    $payload = (new SeatPlanResource($plan))->resolve();

    $firstBlockLabels = collect($payload['blocks'][0]['labels'])->pluck('title')->all();
    expect($firstBlockLabels)->toContain('Stage')
        ->and($firstBlockLabels)->toContain('Entrance');
});

it('prefixes seat titles with the block seat_title_prefix on the wire', function (): void {
    $plan = SeatPlan::factory()->empty()->withBlocks([[
        'title' => 'VIP',
        'color' => '#f59e0b',
        'seat_title_prefix' => 'VIP-',
        'rows' => [['name' => 'A', 'seats' => [
            ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
            ['number' => 2, 'title' => 'A2', 'x' => 30, 'y' => 0, 'salable' => true],
        ]]],
    ]])->create();

    $plan->load(['blocks.seats', 'blocks.labels', 'blocks.categoryRestrictions']);

    $payload = (new SeatPlanResource($plan))->resolve();

    expect($payload['blocks'][0]['seats'][0]['title'])->toBe('VIP-A1')
        ->and($payload['blocks'][0]['seats'][1]['title'])->toBe('VIP-A2');
});
