<?php

namespace App\Domain\Seating\Http\Resources;

use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanLabel;
use App\Domain\Seating\Models\SeatPlanRow;
use App\Domain\Seating\Models\SeatPlanSeat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Editor-oriented projection of a seat plan. Superset of SeatPlanResource —
 * adds explicit `rows` per block and per-seat `row_id`/`number` so the admin
 * editor can render + manipulate the row structure directly.
 *
 * Callers should eager-load `blocks.rows`, `blocks.seats.row`,
 * `blocks.labels`, and `blocks.categoryRestrictions`.
 *
 * @mixin SeatPlan
 */
class SeatPlanEditorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'event_id' => $this->event_id,
            'background_image_url' => $this->background_image_url,
            'labels' => $this->resource->relationLoaded('globalLabels')
                ? $this->globalLabels->map(fn (SeatPlanLabel $label): array => [
                    'id' => $label->id,
                    'title' => $label->title,
                    'x' => $label->x,
                    'y' => $label->y,
                    'sort_order' => $label->sort_order,
                ])->values()->all()
                : [],
            'blocks' => $this->blocks->map(fn (SeatPlanBlock $block): array => [
                'id' => $block->id,
                'title' => $block->title,
                'color' => $block->color,
                'seat_title_prefix' => $block->seat_title_prefix,
                'background_image_url' => $block->background_image_url,
                'sort_order' => $block->sort_order,
                'rows' => $block->rows->map(fn (SeatPlanRow $row): array => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'sort_order' => $row->sort_order,
                ])->values()->all(),
                'seats' => $block->seats->map(fn (SeatPlanSeat $seat): array => [
                    'id' => $seat->id,
                    'row_id' => $seat->seat_plan_row_id,
                    'number' => $seat->number,
                    'title' => $seat->title,
                    'x' => $seat->x,
                    'y' => $seat->y,
                    'salable' => $seat->salable,
                    'color' => $seat->color,
                    'note' => $seat->note,
                    'custom_data' => $seat->custom_data,
                ])->values()->all(),
                'labels' => $block->labels->map(fn (SeatPlanLabel $label): array => [
                    'id' => $label->id,
                    'title' => $label->title,
                    'x' => $label->x,
                    'y' => $label->y,
                    'sort_order' => $label->sort_order,
                ])->values()->all(),
                'allowed_ticket_category_ids' => $block->categoryRestrictions
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->values()
                    ->all(),
            ])->values()->all(),
        ];
    }
}
