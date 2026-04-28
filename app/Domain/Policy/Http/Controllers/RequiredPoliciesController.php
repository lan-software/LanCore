<?php

namespace App\Domain\Policy\Http\Controllers;

use App\Domain\Policy\Actions\RecordPolicyAcceptance;
use App\Domain\Policy\Enums\PolicyAcceptanceSource;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-005
 * @see docs/mil-std-498/SRS.md POL-F-011, POL-F-012
 */
class RequiredPoliciesController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        $activeRequired = Policy::query()
            ->active()
            ->whereNotNull('required_acceptance_version_id')
            ->with('requiredAcceptanceVersion', 'type')
            ->orderBy('sort_order')
            ->get();

        $acceptedVersionIds = $user->policyAcceptances()
            ->whereNull('withdrawn_at')
            ->pluck('policy_version_id')
            ->all();

        $unaccepted = $activeRequired
            ->filter(fn (Policy $p) => ! in_array($p->required_acceptance_version_id, $acceptedVersionIds, true))
            ->values();

        return Inertia::render('policies/Required', [
            'policies' => $unaccepted,
            'intendedUrl' => $request->session()->get('url.intended'),
        ]);
    }

    public function accept(Request $request, RecordPolicyAcceptance $recordAcceptance): RedirectResponse
    {
        $validated = $request->validate([
            'policy_version_ids' => ['required', 'array', 'min:1'],
            'policy_version_ids.*' => ['integer', 'exists:policy_versions,id'],
        ]);

        $user = $request->user();
        $versions = PolicyVersion::query()->whereIn('id', $validated['policy_version_ids'])->get();

        foreach ($versions as $version) {
            $recordAcceptance->execute($user, $version, PolicyAcceptanceSource::ReAcceptanceGate, $request);
        }

        return redirect()->intended(route('dashboard'));
    }
}
