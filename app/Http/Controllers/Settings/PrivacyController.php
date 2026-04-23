<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md SET-F-009, SET-F-010
 */
class PrivacyController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Privacy', [
            'isSeatVisiblePublicly' => (bool) $user->is_seat_visible_publicly,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_seat_visible_publicly' => ['required', 'boolean'],
        ]);

        $request->user()->update([
            'is_seat_visible_publicly' => $validated['is_seat_visible_publicly'],
        ]);

        return back();
    }
}
