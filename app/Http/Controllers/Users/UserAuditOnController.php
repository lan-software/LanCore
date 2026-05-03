<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Audit log entries describing changes made TO this user
 * (i.e. rows where the User is the auditable subject).
 *
 * @see docs/mil-std-498/SRS.md USR-F-014
 */
class UserAuditOnController extends Controller
{
    public function __invoke(User $user): Response
    {
        $this->authorize('viewAudit', $user);

        $audits = $user->audits()
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

        return Inertia::render('users/Audit', [
            'user' => $user->only('id', 'name', 'email'),
            'perspective' => 'on',
            'audits' => $audits,
        ]);
    }
}
