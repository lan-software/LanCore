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
            $eventData['banner_image_url'] = $event->banner_image ? Storage::fileUrl($event->banner_image) : null;

            return $eventData;
        });

        return Inertia::render('events/Public', [
            'events' => $events,
        ]);
    }
}
