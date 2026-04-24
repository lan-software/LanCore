<?php

use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Support\LegacySeatPlanConverter;
use App\Domain\Ticketing\Models\TicketCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->converter = app(LegacySeatPlanConverter::class);
});

it('is idempotent — running the backfill twice creates no duplicate blocks', function (): void {
    $plan = SeatPlan::factory()->empty()->withBlocks([[
        'title' => 'Main',
        'color' => '#fff',
        'rows' => [['name' => 'A', 'seats' => [['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true]]]],
    ]])->create();

    $first = $this->converter->backfillAll();
    $second = $this->converter->backfillAll();

    expect($first['skipped'])->toBe(1)
        ->and($second['skipped'])->toBe(1)
        ->and($plan->blocks()->count())->toBe(1);
});

it('migrates a plan that has no normalized blocks (dev DB scenario)', function (): void {
    $plan = SeatPlan::factory()->empty()->create();
    $category = TicketCategory::factory()->create(['event_id' => $plan->event_id]);

    $legacy = ['blocks' => [[
        'id' => 'main',
        'title' => 'Main',
        'color' => '#1f6feb',
        'allowed_ticket_category_ids' => [$category->id],
        'seats' => [
            ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true, 'note' => 'aisle'],
            ['id' => 'A2', 'title' => 'A2', 'x' => 30, 'y' => 0, 'salable' => true],
        ],
        'labels' => [['title' => 'Row A', 'x' => -30, 'y' => 0]],
    ]]];

    DB::table('seat_plans')->where('id', $plan->id)->update(['data' => json_encode($legacy)]);

    $result = $this->converter->backfillPlan($plan);

    $blocks = $plan->fresh()->blocks()->with('seats', 'labels', 'categoryRestrictions')->get();
    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]->seats)->toHaveCount(2)
        ->and($blocks[0]->labels)->toHaveCount(1)
        ->and($blocks[0]->categoryRestrictions->pluck('id')->all())->toBe([$category->id])
        ->and($result['orphans'])->toBe([]);
})->skip(
    fn () => ! Schema::hasColumn('seat_plans', 'data'),
    'Legacy data column has already been dropped.',
);
