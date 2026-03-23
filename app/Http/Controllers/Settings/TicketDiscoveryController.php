<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketDiscoveryController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/TicketDiscovery', [
            'isTicketDiscoverable' => (bool) $user->is_ticket_discoverable,
            'ticketDiscoveryAllowlist' => $user->ticket_discovery_allowlist ?? [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_ticket_discoverable' => ['required', 'boolean'],
            'ticket_discovery_allowlist' => ['nullable', 'array'],
            'ticket_discovery_allowlist.*' => ['string', 'max:255'],
        ]);

        $allowlist = $validated['is_ticket_discoverable']
            ? []
            : array_values(array_unique(array_filter($validated['ticket_discovery_allowlist'] ?? [])));

        $request->user()->update([
            'is_ticket_discoverable' => $validated['is_ticket_discoverable'],
            'ticket_discovery_allowlist' => $allowlist,
        ]);

        return back();
    }
}
