<?php

namespace App\Domain\Event\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-EVT-006
 * @see docs/mil-std-498/SRS.md EVT-F-008
 */
class EventAuditController extends Controller
{
    public function __invoke(Event $event): Response
    {
        $this->authorize('viewAudit', $event);

        $audits = $event->audits()
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

        return Inertia::render('events/Audit', [
            'event' => $event->only('id', 'name'),
            'audits' => $audits,
        ]);
    }
}
