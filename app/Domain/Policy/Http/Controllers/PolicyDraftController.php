<?php

namespace App\Domain\Policy\Http\Controllers;

use App\Domain\Policy\Http\Requests\StorePolicyDraftRequest;
use App\Domain\Policy\Http\Requests\UpdatePolicyDraftRequest;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyLocaleDraft;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for per-locale drafts. Drafts are mutable WIP — saved to the database
 * so admins don't lose progress across browser refreshes — and snapshotted
 * into `policy_versions` rows on publish.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-003
 */
class PolicyDraftController extends Controller
{
    public function store(StorePolicyDraftRequest $request, Policy $policy): RedirectResponse
    {
        $this->authorize('update', $policy);

        $policy->drafts()->create([
            'locale' => $request->validated('locale'),
            'content' => '',
            'updated_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', __('policies.drafts.flash.added'));
    }

    public function update(UpdatePolicyDraftRequest $request, Policy $policy, string $locale): RedirectResponse
    {
        $this->authorize('update', $policy);

        $draft = $policy->drafts()->where('locale', $locale)->firstOrFail();

        $draft->forceFill([
            'content' => (string) ($request->validated('content') ?? ''),
            'updated_by_user_id' => $request->user()->id,
        ])->save();

        return back()->with('success', __('policies.drafts.flash.saved'));
    }

    public function destroy(Policy $policy, string $locale): RedirectResponse
    {
        $this->authorize('update', $policy);

        $draft = $policy->drafts()->where('locale', $locale)->firstOrFail();

        if ($policy->drafts()->count() <= 1) {
            throw ValidationException::withMessages([
                'locale' => __('policies.drafts.errors.cannot_remove_last_locale'),
            ]);
        }

        /** @var PolicyLocaleDraft $draft */
        $draft->delete();

        return back()->with('success', __('policies.drafts.flash.removed'));
    }
}
