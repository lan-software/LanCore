<?php

namespace App\Domain\Policy\Http\Controllers;

use App\Domain\Policy\Actions\CreatePolicyType;
use App\Domain\Policy\Actions\DeletePolicyType;
use App\Domain\Policy\Actions\UpdatePolicyType;
use App\Domain\Policy\Exceptions\PolicyTypeInUseException;
use App\Domain\Policy\Http\Requests\StorePolicyTypeRequest;
use App\Domain\Policy\Http\Requests\UpdatePolicyTypeRequest;
use App\Domain\Policy\Models\PolicyType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-002
 * @see docs/mil-std-498/SRS.md POL-F-006
 */
class PolicyTypeController extends Controller
{
    public function __construct(
        private readonly CreatePolicyType $createPolicyType,
        private readonly UpdatePolicyType $updatePolicyType,
        private readonly DeletePolicyType $deletePolicyType,
    ) {}

    public function store(StorePolicyTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', PolicyType::class);

        $this->createPolicyType->execute($request->validated());

        return back()->with('success', __('policies.types.flash.created'));
    }

    public function update(UpdatePolicyTypeRequest $request, PolicyType $policyType): RedirectResponse
    {
        $this->authorize('update', $policyType);

        $this->updatePolicyType->execute($policyType, $request->validated());

        return back()->with('success', __('policies.types.flash.updated'));
    }

    public function destroy(PolicyType $policyType): RedirectResponse
    {
        $this->authorize('delete', $policyType);

        try {
            $this->deletePolicyType->execute($policyType);
        } catch (PolicyTypeInUseException $exception) {
            return back()->withErrors(['policyType' => $exception->getMessage()]);
        }

        return back()->with('success', __('policies.types.flash.deleted'));
    }
}
