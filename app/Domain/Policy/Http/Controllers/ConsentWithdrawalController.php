<?php

namespace App\Domain\Policy\Http\Controllers;

use App\Domain\Policy\Actions\WithdrawPolicyConsent;
use App\Domain\Policy\Exceptions\NoActivePolicyAcceptanceException;
use App\Domain\Policy\Models\Policy;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConsentWithdrawalController extends Controller
{
    public function store(
        Request $request,
        Policy $policy,
        WithdrawPolicyConsent $withdraw,
    ): RedirectResponse {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $withdraw->execute(
                $request->user(),
                $policy,
                $data['reason'] ?? null,
                $request,
            );
        } catch (NoActivePolicyAcceptanceException) {
            return back()->withErrors([
                'consent' => __('policies.consent.withdraw.no_active_acceptance'),
            ]);
        }

        return back()->with('success', __('policies.consent.withdraw.success'));
    }
}
