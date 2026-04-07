<?php

namespace App\Http\Controllers;

use App\Domain\Event\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventContextController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        $request->session()->put('selected_event_id', (int) $request->input('event_id'));

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('selected_event_id');

        return back();
    }

    public function storeMy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        $eventId = (int) $validated['event_id'];
        $user = $request->user();

        $isAllowed = Event::query()->forUser($user)->whereKey($eventId)->exists();

        if (! $isAllowed) {
            throw ValidationException::withMessages([
                'event_id' => 'You do not have access to this event.',
            ]);
        }

        $request->session()->put('my_selected_event_id', $eventId);

        return back();
    }

    public function destroyMy(Request $request): RedirectResponse
    {
        $request->session()->forget('my_selected_event_id');

        return back();
    }
}
