<?php

namespace App\Domain\DataLifecycle\Http\Controllers;

use App\Domain\DataLifecycle\Actions\UpdateRetentionPolicy;
use App\Domain\DataLifecycle\Http\Requests\UpdateRetentionPolicyRequest;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin UI for editing the per-data-class retention windows.
 *
 * @see docs/mil-std-498/SRS.md DL-F-011, DL-F-012
 */
class AdminRetentionPolicyController extends Controller
{
    public function __construct(private readonly UpdateRetentionPolicy $updateRetentionPolicy) {}

    public function index(): Response
    {
        $this->authorize('viewAny', RetentionPolicy::class);

        $policies = RetentionPolicy::query()
            ->orderBy('data_class')
            ->get();

        return Inertia::render('admin/data-lifecycle/RetentionPolicies/Index', [
            'policies' => $policies,
        ]);
    }

    public function update(UpdateRetentionPolicyRequest $request, RetentionPolicy $retentionPolicy): RedirectResponse
    {
        $this->authorize('update', $retentionPolicy);

        $this->updateRetentionPolicy->execute(
            policy: $retentionPolicy,
            attributes: $request->validated(),
            editor: $request->user(),
        );

        return back()->with('status', 'Retention policy updated.');
    }
}
