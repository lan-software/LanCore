<?php

namespace App\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Program\Enums\ProgramVisibility;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        $nextEvent = Event::published()
            ->upcoming()
            ->with([
                'venue.address',
                'venue.images',
                'programs' => fn ($q) => $q->where('visibility', ProgramVisibility::Public)->orderBy('sort_order'),
                'programs.timeSlots' => fn ($q) => $q->where('visibility', ProgramVisibility::Public)->orderBy('starts_at'),
            ])
            ->orderBy('start_date')
            ->first();

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'nextEvent' => $nextEvent,
        ]);
    }
}
