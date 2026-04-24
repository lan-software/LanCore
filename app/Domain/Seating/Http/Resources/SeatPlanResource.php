<?php

namespace App\Domain\Seating\Http\Resources;

use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanLabel;
use App\Domain\Seating\Models\SeatPlanSeat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wire-shape compatible projection of a seat plan for the Picker and any
 * consumer of the seatmap-canvas blocks/seats shape. The output preserves the
 * pre-normalization JSON contract so `SeatMapCanvas.vue` does not need to
 * change.
 *
 * Callers should eager-load `blocks.seats`, `blocks.labels`,
 * `blocks.categoryRestrictions`, and `globalLabels` to avoid N+1.
 *
 * The resource strips blocks with zero seats (empty admin placeholders) and
 * flattens plan-level labels into the first remaining block, because the
 * `@alisaitteke/seatmap-canvas` library (a) breaks its venue-fit bbox when a
 * block has no seats and (b) requires labels to live under a block
 * (BlockModel.labels: LabelModel[]).
 *
 * @mixin SeatPlan
 *
 * @see docs/mil-std-498/IDD.md §3.14 Seat Picker
 */
class SeatPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var list<array{id: int|null, title: string, x: int, y: int}> $globalLabelRows */
        $globalLabelRows = $this->resource->relationLoaded('globalLabels')
            ? $this->globalLabels->map(fn ($label): array => [
                'id' => $label->id,
                'title' => $label->title,
                'x' => $label->x,
                'y' => $label->y,
            ])->values()->all()
            : [];

        /* Drop blocks with no seats — they have nothing to render and their
         * empty bbox poisons the library's venue-fit zoom, making the whole
         * canvas appear blank. */
        $visibleBlocks = $this->blocks
            ->filter(fn (SeatPlanBlock $block) => $block->seats->isNotEmpty())
            ->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'event_id' => $this->event_id,
            'background_image_url' => $this->background_image_url,
            'blocks' => $visibleBlocks->map(fn (SeatPlanBlock $block, int $index): array => [
                'id' => $block->id,
                'title' => $block->title,
                'color' => $block->color,
                'background_image_url' => $block->background_image_url,
                'sort_order' => $block->sort_order,
                'seats' => $block->seats->map(fn (SeatPlanSeat $seat): array => [
                    'id' => $seat->id,
                    'title' => ($block->seat_title_prefix ?? '').$seat->title,
                    'x' => $seat->x,
                    'y' => $seat->y,
                    'salable' => $seat->salable,
                    'color' => $seat->color,
                    'note' => $seat->note,
                    'custom_data' => $seat->custom_data,
                ])->values()->all(),
                'labels' => [
                    ...$block->labels->map(fn (SeatPlanLabel $label): array => [
                        'id' => $label->id,
                        'title' => $label->title,
                        'x' => $label->x,
                        'y' => $label->y,
                    ])->values()->all(),
                    /* Attach plan-level labels to the first non-empty block
                     * so they reach the library while staying visually
                     * associated with the canvas. */
                    ...($index === 0 ? $globalLabelRows : []),
                ],
                'allowed_ticket_category_ids' => $block->categoryRestrictions
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->values()
                    ->all(),
            ])->values()->all(),
        ];
    }
}
