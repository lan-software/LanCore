<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanLabel;
use App\Domain\Seating\Models\SeatPlanRow;
use App\Domain\Seating\Models\SeatPlanSeat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatPlan>
 */
class SeatPlanFactory extends Factory
{
    protected $model = SeatPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'event_id' => Event::factory(),
            'background_image_url' => null,
        ];
    }

    /**
     * Default state: one block with a single row and two salable seats plus a
     * row label. Mirrors the shape demo seeders and picker tests expect.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (SeatPlan $plan): void {
            if ($plan->blocks()->exists()) {
                return;
            }

            $block = SeatPlanBlock::query()->create([
                'seat_plan_id' => $plan->id,
                'title' => 'Main Hall',
                'color' => fake()->hexColor(),
                'sort_order' => 0,
            ]);

            $row = SeatPlanRow::query()->create([
                'seat_plan_block_id' => $block->id,
                'name' => 'A',
                'sort_order' => 0,
            ]);

            SeatPlanSeat::query()->create([
                'seat_plan_id' => $plan->id,
                'seat_plan_block_id' => $block->id,
                'seat_plan_row_id' => $row->id,
                'number' => 1,
                'title' => 'A1',
                'x' => 0,
                'y' => 0,
                'salable' => true,
            ]);

            SeatPlanSeat::query()->create([
                'seat_plan_id' => $plan->id,
                'seat_plan_block_id' => $block->id,
                'seat_plan_row_id' => $row->id,
                'number' => 2,
                'title' => 'A2',
                'x' => 30,
                'y' => 0,
                'salable' => true,
            ]);

            SeatPlanLabel::query()->create([
                'seat_plan_id' => $plan->id,
                'seat_plan_block_id' => $block->id,
                'title' => 'Row A',
                'x' => -30,
                'y' => 0,
                'sort_order' => 0,
            ]);
        });
    }

    /**
     * Skip the default block/row/seat creation.
     */
    public function empty(): static
    {
        return $this->afterCreating(function (SeatPlan $plan): void {
            $plan->blocks()->get()->each->delete();
        });
    }

    /**
     * Build the block tree from an array shape:
     * [
     *   [
     *     'title' => 'Main',
     *     'color' => '#fff',
     *     'allowed_ticket_category_ids' => [1, 2],
     *     'rows' => [
     *       ['name' => 'A', 'seats' => [['title' => 'A1', 'x' => 0, 'y' => 0]]],
     *     ],
     *     'labels' => [['title' => 'Row A', 'x' => -30, 'y' => 0]],
     *   ]
     * ]
     *
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function withBlocks(array $blocks): static
    {
        return $this->afterCreating(function (SeatPlan $plan) use ($blocks): void {
            $plan->blocks()->get()->each->delete();

            foreach ($blocks as $blockIndex => $blockPayload) {
                $block = SeatPlanBlock::query()->create([
                    'seat_plan_id' => $plan->id,
                    'title' => (string) ($blockPayload['title'] ?? "Block {$blockIndex}"),
                    'color' => (string) ($blockPayload['color'] ?? '#2c3e50'),
                    'seat_title_prefix' => is_string($blockPayload['seat_title_prefix'] ?? null) && $blockPayload['seat_title_prefix'] !== ''
                        ? $blockPayload['seat_title_prefix']
                        : null,
                    'background_image_url' => $blockPayload['background_image_url'] ?? null,
                    'sort_order' => (int) ($blockPayload['sort_order'] ?? $blockIndex),
                ]);

                $categoryIds = array_values(array_filter(array_map(
                    fn (mixed $id): ?int => is_numeric($id) ? (int) $id : null,
                    (array) ($blockPayload['allowed_ticket_category_ids'] ?? []),
                ), fn (?int $id): bool => $id !== null));

                if ($categoryIds !== []) {
                    $block->categoryRestrictions()->sync($categoryIds);
                }

                foreach ((array) ($blockPayload['rows'] ?? []) as $rowIndex => $rowPayload) {
                    $row = SeatPlanRow::query()->create([
                        'seat_plan_block_id' => $block->id,
                        'name' => (string) ($rowPayload['name'] ?? chr(65 + $rowIndex)),
                        'sort_order' => (int) ($rowPayload['sort_order'] ?? $rowIndex),
                    ]);

                    foreach ((array) ($rowPayload['seats'] ?? []) as $seatIndex => $seatPayload) {
                        SeatPlanSeat::query()->create([
                            'seat_plan_id' => $plan->id,
                            'seat_plan_block_id' => $block->id,
                            'seat_plan_row_id' => $row->id,
                            'number' => isset($seatPayload['number']) ? (int) $seatPayload['number'] : ($seatIndex + 1),
                            'title' => (string) ($seatPayload['title'] ?? $row->name.($seatIndex + 1)),
                            'x' => (int) ($seatPayload['x'] ?? ($seatIndex * 30)),
                            'y' => (int) ($seatPayload['y'] ?? ($rowIndex * 30)),
                            'salable' => (bool) ($seatPayload['salable'] ?? true),
                            'color' => $seatPayload['color'] ?? null,
                            'note' => $seatPayload['note'] ?? null,
                            'custom_data' => is_array($seatPayload['custom_data'] ?? null) ? $seatPayload['custom_data'] : null,
                        ]);
                    }
                }

                foreach ((array) ($blockPayload['labels'] ?? []) as $labelIndex => $labelPayload) {
                    SeatPlanLabel::query()->create([
                        'seat_plan_id' => $plan->id,
                        'seat_plan_block_id' => $block->id,
                        'title' => (string) ($labelPayload['title'] ?? ''),
                        'x' => (int) ($labelPayload['x'] ?? 0),
                        'y' => (int) ($labelPayload['y'] ?? 0),
                        'sort_order' => (int) ($labelPayload['sort_order'] ?? $labelIndex),
                    ]);
                }
            }
        });
    }
}
