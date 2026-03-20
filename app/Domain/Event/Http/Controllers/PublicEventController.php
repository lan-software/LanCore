<?php

namespace App\Domain\Event\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Http\Controllers\Controller;
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

        return Inertia::render('events/Public', [
            'events' => $events,
        ]);
    }
}
