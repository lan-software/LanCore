<?php

namespace App\Domain\Seating\Http\Controllers;

use App\Domain\Seating\Models\SeatPlan;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SeatPlanAuditController extends Controller
{
    public function __invoke(SeatPlan $seatPlan): Response
    {
        $this->authorize('viewAudit', $seatPlan);

        $audits = $seatPlan->audits()
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

        return Inertia::render('seating/Audit', [
            'seatPlan' => $seatPlan->only('id', 'name'),
            'audits' => $audits,
        ]);
    }
}
