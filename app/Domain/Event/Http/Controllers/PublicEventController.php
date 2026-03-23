<?php

namespace App\Domain\Event\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

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
            $bannerImages = array_filter($event->banner_images ?? [], fn ($p) => is_string($p) && $p !== '');
            $eventData['banner_image_urls'] = array_values(array_map(
                fn (string $path) => Storage::url($path),
                $bannerImages,
            ));

            return $eventData;
        });

        return Inertia::render('events/Public', [
            'events' => $events,
        ]);
    }
}
