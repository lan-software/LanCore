<?php

namespace App\Domain\Policy\Http\Controllers;

use App\Domain\Policy\Actions\ArchivePolicy;
use App\Domain\Policy\Actions\CreatePolicy;
use App\Domain\Policy\Actions\UpdatePolicy;
use App\Domain\Policy\Http\Requests\StorePolicyRequest;
use App\Domain\Policy\Http\Requests\UpdatePolicyRequest;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-001
 * @see docs/mil-std-498/SRS.md POL-F-001..005
 */
class PolicyController extends Controller
{
    public function __construct(
        private readonly CreatePolicy $createPolicy,
        private readonly UpdatePolicy $updatePolicy,
        private readonly ArchivePolicy $archivePolicy,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Policy::class);

        $policies = Policy::with(['type', 'currentVersion'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/policies/Index', [
            'policies' => $policies,
            'policyTypes' => PolicyType::orderBy('label')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Policy::class);

        return Inertia::render('admin/policies/Create', [
            'policyTypes' => PolicyType::orderBy('label')->get(),
        ]);
    }

    public function store(StorePolicyRequest $request): RedirectResponse
    {
        $this->authorize('create', Policy::class);

        $policy = $this->createPolicy->execute($request->validated());

        return redirect()->route('admin.policies.show', $policy)
            ->with('success', __('policies.flash.created'));
    }

    public function show(Policy $policy): Response
    {
        $this->authorize('view', $policy);

        $policy->load([
            'type',
            'requiredAcceptanceVersion',
            'versions' => fn ($q) => $q->orderByDesc('version_number'),
            'versions.publishedBy',
        ]);

        return Inertia::render('admin/policies/Show', [
            'policy' => $policy,
        ]);
    }

    public function edit(Policy $policy): Response
    {
        $this->authorize('update', $policy);

        $policy->load(['type']);

        return Inertia::render('admin/policies/Edit', [
            'policy' => $policy,
            'policyTypes' => PolicyType::orderBy('label')->get(),
        ]);
    }

    public function update(UpdatePolicyRequest $request, Policy $policy): RedirectResponse
    {
        $this->authorize('update', $policy);

        $this->updatePolicy->execute($policy, $request->validated());

        return back()->with('success', __('policies.flash.updated'));
    }

    public function archive(Policy $policy): RedirectResponse
    {
        $this->authorize('delete', $policy);

        $this->archivePolicy->execute($policy);

        return redirect()->route('admin.policies.index')
            ->with('success', __('policies.flash.archived'));
    }
}
