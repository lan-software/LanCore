<?php

namespace App\Domain\Seating\Http\Resources;

use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanLabel;
use App\Domain\Seating\Models\SeatPlanSeat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wire-shape projection of a seat plan for the read-side Picker / Welcome /
 * editor-preview consumers. Mirrors the normalized database schema directly:
 * blocks contain seats and per-block labels; plan-level labels live at the
 * top level alongside `blocks`. The frontend `SeatPlanScene` renders this
 * shape as-is without any zone↔block translation.
 *
 * Callers should eager-load `blocks.seats`, `blocks.labels`,
 * `blocks.categoryRestrictions`, and `globalLabels` to avoid N+1.
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
                ])->values()->all()
                : [],
            'blocks' => $this->blocks->map(fn (SeatPlanBlock $block): array => [
                'id' => $block->id,
                'title' => $block->title,
                'color' => $block->color,
                /* Exposed raw so the renderer can prepend it at display time;
                 * the picker UI also reads it to format seat titles in the
                 * action bar and zoom hints. */
                'seat_title_prefix' => $block->seat_title_prefix,
                'background_image_url' => $block->background_image_url,
                'sort_order' => $block->sort_order,
                'seats' => $block->seats->map(fn (SeatPlanSeat $seat): array => [
                    'id' => $seat->id,
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
                ])->values()->all(),
                'allowed_ticket_category_ids' => $block->categoryRestrictions
                    ->pluck('id')
                    ->map(fn ($id): string => (string) $id)
                    ->values()
                    ->all(),
            ])->values()->all(),
        ];
    }
}
