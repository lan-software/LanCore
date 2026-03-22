<?php

namespace App\Domain\Ticketing\Http\Controllers;

use App\Domain\Ticketing\Models\TicketType;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TicketTypeAuditController extends Controller
{
    public function __invoke(TicketType $ticketType): Response
    {
        $this->authorize('viewAudit', $ticketType);

        $audits = $ticketType->audits()
            ->with('user')
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->through(fn ($audit) => [
                'id' => $audit->id,
                'event' => $audit->event,
                'old_values' => $audit->old_values,
                'new_values' => $audit->new_values,
                'url' => $audit->url,
                'ip_address' => $audit->ip_address,
                'user_agent' => $audit->user_agent,
                'tags' => $audit->tags,
                'created_at' => $audit->created_at->toIso8601String(),
                'user' => $audit->user ? [
                    'id' => $audit->user->id,
                    'name' => $audit->user->name,
                    'email' => $audit->user->email,
                ] : null,
            ]);

        return Inertia::render('ticket-types/Audit', [
            'ticketType' => $ticketType->only('id', 'name'),
            'audits' => $audits,
        ]);
    }
}
