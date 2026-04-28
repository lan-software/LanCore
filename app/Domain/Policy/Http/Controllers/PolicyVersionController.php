<?php

namespace App\Domain\Policy\Http\Controllers;

use App\Domain\Policy\Actions\PublishPolicyVersion;
use App\Domain\Policy\Http\Requests\StorePolicyVersionRequest;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-003
 * @see docs/mil-std-498/SRS.md POL-F-007..009, POL-F-018
 */
class PolicyVersionController extends Controller
{
    public function __construct(
        private readonly PublishPolicyVersion $publishPolicyVersion,
    ) {}

    /**
     * Legacy URL — the per-locale create page is gone; the multi-locale draft
     * editor lives on the policy show page now.
     */
    public function create(Policy $policy): RedirectResponse
    {
        return redirect()->route('admin.policies.show', $policy);
    }

    public function store(StorePolicyVersionRequest $request, Policy $policy): RedirectResponse
    {
        $this->authorize('create', PolicyVersion::class);

        $effectiveAt = $request->validated('effective_at')
            ? CarbonImmutable::parse($request->validated('effective_at'))
            : null;

        $this->publishPolicyVersion->execute(
            policy: $policy,
            isNonEditorial: $request->boolean('is_non_editorial_change'),
            publicStatement: $request->validated('public_statement'),
            publishedBy: $request->user(),
            effectiveAt: $effectiveAt,
        );

        return redirect()->route('admin.policies.show', $policy)
            ->with('success', __('policies.versions.flash.published'));
    }
}
