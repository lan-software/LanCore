<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use OwenIt\Auditing\Models\Audit;

/**
 * Audit log entries describing changes made BY this user
 * (i.e. rows where the User is the actor on any auditable subject).
 *
 * @see docs/mil-std-498/SRS.md USR-F-014
 */
class UserAuditByController extends Controller
{
    public function __invoke(User $user): Response
    {
        $this->authorize('viewAudit', $user);

        $audits = Audit::query()
            ->where('user_type', User::class)
            ->where('user_id', $user->getKey())
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->through(fn (Audit $audit) => [
                'id' => $audit->id,
                'event' => $audit->event,
                'auditable_type' => $audit->auditable_type,
                'auditable_id' => $audit->auditable_id,
                'old_values' => $audit->old_values,
                'new_values' => $audit->new_values,
                'url' => $audit->url,
                'ip_address' => $audit->ip_address,
                'user_agent' => $audit->user_agent,
                'tags' => $audit->tags,
                'created_at' => $audit->created_at->toIso8601String(),
            ]);

        return Inertia::render('users/Audit', [
            'user' => $user->only('id', 'name', 'email'),
            'perspective' => 'by',
            'audits' => $audits,
        ]);
    }
}
