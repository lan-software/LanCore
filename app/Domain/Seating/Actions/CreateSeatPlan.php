<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Support\SeatPlanTreeSyncer;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-SET-001
 * @see docs/mil-std-498/SRS.md SET-F-001, SET-F-002
 */
class CreateSeatPlan
{
    public function __construct(
        private readonly SeatPlanTreeSyncer $syncer,
    ) {}

    /**
     * @param  array{name: string, event_id: int, background_image_url?: string|null, data?: array<string, mixed>}  $attributes
     */
    public function execute(array $attributes): SeatPlan
    {
        $blocks = array_values((array) ($attributes['data']['blocks'] ?? []));
        $planLabels = array_values((array) ($attributes['data']['labels'] ?? []));

        return DB::transaction(function () use ($attributes, $blocks, $planLabels): SeatPlan {
            /** @var SeatPlan $plan */
            $plan = SeatPlan::query()->create([
                'name' => $attributes['name'],
                'event_id' => $attributes['event_id'],
                'background_image_url' => $attributes['background_image_url'] ?? null,
            ]);

            $this->syncer->sync($plan, $blocks, $planLabels);

            return $plan;
        });
    }
}
