<?php

namespace App\Domain\Policy\Http\Controllers;

use App\Domain\Policy\Actions\RecordPolicyAcceptance;
use App\Domain\Policy\Enums\PolicyAcceptanceSource;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyAcceptance;
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
        $locale = (string) app()->getLocale();

        $activeRequired = Policy::query()
            ->active()
            ->whereNotNull('required_acceptance_version_number')
            ->with('type')
            ->orderBy('sort_order')
            ->get();

        $unaccepted = $activeRequired->filter(function (Policy $policy) use ($user): bool {
            return ! PolicyAcceptance::query()
                ->where('user_id', $user->id)
                ->whereNull('withdrawn_at')
                ->whereHas(
                    'version',
                    fn ($q) => $q
                        ->where('policy_id', $policy->id)
                        ->where('version_number', $policy->required_acceptance_version_number),
                )
                ->exists();
        })->values();

        $payload = $unaccepted->map(function (Policy $policy) use ($locale): ?array {
            $version = $policy->versionForLocale(
                (int) $policy->required_acceptance_version_number,
                $locale,
            );

            if ($version === null) {
                return null;
            }

            return [
                'id' => $policy->id,
                'key' => $policy->key,
                'name' => $policy->name,
                'description' => $policy->description,
                'type' => $policy->type ? [
                    'key' => $policy->type->key,
                    'label' => $policy->type->label,
                ] : null,
                'required_acceptance_version' => [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'locale' => $version->locale,
                    'content' => $version->content,
                    'public_statement' => $version->public_statement,
                ],
            ];
        })->filter()->values();

        return Inertia::render('policies/Required', [
            'policies' => $payload,
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
