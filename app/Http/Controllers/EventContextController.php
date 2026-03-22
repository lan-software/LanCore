<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
}
