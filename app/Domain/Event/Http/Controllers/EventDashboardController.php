<?php

namespace App\Domain\Event\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Event\Services\EventDashboardStats;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventDashboardController extends Controller
{
    public function __construct(private readonly EventDashboardStats $stats) {}

    public function show(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $event = $this->resolveEvent($request);

        return Inertia::render('events/Dashboard', [
            'stats' => $event ? $this->stats->forEvent($event) : null,
            'generatedAt' => now()->toISOString(),
        ]);
    }

    private function resolveEvent(Request $request): ?Event
    {
        $sessionId = $request->session()->get('selected_event_id');

        if ($sessionId) {
            $selected = Event::query()->find($sessionId);

            if ($selected) {
                return $selected;
            }
        }

        $active = Event::query()->published()->active()->orderBy('start_date')->first();

        if ($active) {
            return $active;
        }

        return Event::query()->published()->upcoming()->orderBy('start_date')->first();
    }
}
