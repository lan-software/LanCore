<?php

namespace App\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Support\StorageRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-CTD-001
 * @see docs/mil-std-498/SRS.md CTD-F-001
 */
class CountdownController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $nextEvent = Event::published()
            ->upcoming()
            ->orderBy('start_date')
            ->first(['id', 'name', 'start_date', 'banner_images']);

        $eventPayload = null;

        if ($nextEvent !== null) {
            $bannerImages = array_values(array_filter(
                $nextEvent->banner_images ?? [],
                fn ($p) => is_string($p) && $p !== '',
            ));

            $eventPayload = [
                'id' => $nextEvent->id,
                'name' => $nextEvent->name,
                'start_date' => $nextEvent->start_date?->toIso8601String(),
                'banner_image_urls' => array_map(
                    fn (string $path): string => StorageRole::publicUrl($path),
                    $bannerImages,
                ),
            ];
        }

        $defaultList = NewsletterList::query()
            ->where('is_default_public', true)
            ->first(['id', 'name', 'description']);

        return Inertia::render('Countdown', [
            'event' => $eventPayload,
            'newsletter' => $defaultList === null ? null : [
                'list_id' => $defaultList->id,
                'list_name' => $defaultList->name,
                'list_description' => $defaultList->description,
            ],
        ]);
    }
}
