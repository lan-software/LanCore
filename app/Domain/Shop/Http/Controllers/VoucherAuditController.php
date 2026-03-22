<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Shop\Models\Voucher;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class VoucherAuditController extends Controller
{
    public function __invoke(Voucher $voucher): Response
    {
        $this->authorize('viewAudit', $voucher);

        $audits = $voucher->audits()
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

        return Inertia::render('vouchers/Audit', [
            'voucher' => $voucher->only('id', 'code'),
            'audits' => $audits,
        ]);
    }
}
