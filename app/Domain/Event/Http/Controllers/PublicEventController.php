<?php

namespace App\Domain\Event\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md EVT-F-009
 */
class PublicEventController extends Controller
{
    public function __invoke(): Response
    {
        $events = Event::published()
            ->upcoming()
            ->with('venue')
            ->orderBy('start_date')
            ->paginate(12);

        $events->through(function (Event $event) {
            $eventData = $event->toArray();
            $bannerImages = array_values(array_filter($event->banner_images ?? [], fn ($p) => is_string($p) && $p !== ''));
            $eventData['banner_images'] = $bannerImages;
            $eventData['banner_image_urls'] = array_map(
                fn (string $path) => Storage::fileUrl($path),
                $bannerImages,
            );

            return $eventData;
        });

        return Inertia::render('events/Public', [
            'events' => $events,
        ]);
    }
}
