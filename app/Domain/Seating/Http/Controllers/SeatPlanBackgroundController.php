<?php

namespace App\Domain\Seating\Http\Controllers;

use App\Domain\Seating\Http\Requests\StoreSeatPlanBackgroundRequest;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use App\Http\Controllers\Controller;
use App\Support\StorageRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Upload and delete seat-plan background images (plan-level and per-block).
 * Writes to the public disk under `seat-plans/{plan_id}/…`.
 *
 * @see docs/mil-std-498/IDD.md §3.17 Seat Plan Background Upload
 */
class SeatPlanBackgroundController extends Controller
{
    public function storePlan(StoreSeatPlanBackgroundRequest $request, SeatPlan $seatPlan): RedirectResponse
    {
        $this->authorize('update', $seatPlan);

        $this->deleteFileByUrl($seatPlan->background_image_url);

        $path = $this->storeUpload($request, $seatPlan, 'plan-bg');

        $seatPlan->forceFill(['background_image_url' => StorageRole::publicUrl($path)])->save();

        return back()->with('status', 'seat-plan-background-uploaded');
    }

    public function destroyPlan(SeatPlan $seatPlan): RedirectResponse
    {
        $this->authorize('update', $seatPlan);

        $this->deleteFileByUrl($seatPlan->background_image_url);

        $seatPlan->forceFill(['background_image_url' => null])->save();

        return back()->with('status', 'seat-plan-background-removed');
    }

    public function storeBlock(
        StoreSeatPlanBackgroundRequest $request,
        SeatPlan $seatPlan,
        SeatPlanBlock $block,
    ): RedirectResponse {
        $this->authorize('update', $seatPlan);
        abort_unless($block->seat_plan_id === $seatPlan->id, 404);

        $this->deleteFileByUrl($block->background_image_url);

        $path = $this->storeUpload($request, $seatPlan, "block-{$block->id}-bg");

        $block->forceFill(['background_image_url' => StorageRole::publicUrl($path)])->save();

        return back()->with('status', 'seat-plan-block-background-uploaded');
    }

    public function destroyBlock(SeatPlan $seatPlan, SeatPlanBlock $block): RedirectResponse
    {
        $this->authorize('update', $seatPlan);
        abort_unless($block->seat_plan_id === $seatPlan->id, 404);

        $this->deleteFileByUrl($block->background_image_url);

        $block->forceFill(['background_image_url' => null])->save();

        return back()->with('status', 'seat-plan-block-background-removed');
    }

    private function storeUpload(StoreSeatPlanBackgroundRequest $request, SeatPlan $seatPlan, string $prefix): string
    {
        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $filename = $prefix.'-'.Str::uuid()->toString().'.'.$extension;
        $directory = "seat-plans/{$seatPlan->id}";

        return StorageRole::public()->putFileAs($directory, $file, $filename);
    }

    private function deleteFileByUrl(?string $url): void
    {
        if ($url === null || $url === '') {
            return;
        }

        $disk = StorageRole::public();
        $path = $this->extractPath($url);

        if ($path !== null && $disk->exists($path)) {
            $disk->delete($path);
        }
    }

    private function extractPath(string $url): ?string
    {
        $parsed = parse_url($url, PHP_URL_PATH);

        if (! is_string($parsed) || $parsed === '') {
            return null;
        }

        $marker = 'seat-plans/';
        $position = strpos($parsed, $marker);

        if ($position === false) {
            return null;
        }

        return substr($parsed, $position);
    }
}
