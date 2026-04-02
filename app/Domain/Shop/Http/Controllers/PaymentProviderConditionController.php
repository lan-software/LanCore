<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Shop\Actions\CreatePaymentProviderCondition;
use App\Domain\Shop\Actions\DeletePaymentProviderCondition;
use App\Domain\Shop\Actions\UpdatePaymentProviderCondition;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Http\Requests\StorePaymentProviderConditionRequest;
use App\Domain\Shop\Http\Requests\UpdatePaymentProviderConditionRequest;
use App\Domain\Shop\Models\PaymentProviderCondition;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-007
 * @see docs/mil-std-498/SRS.md SHP-F-010, SHP-F-014
 */
class PaymentProviderConditionController extends Controller
{
    public function __construct(
        private readonly CreatePaymentProviderCondition $createCondition,
        private readonly UpdatePaymentProviderCondition $updateCondition,
        private readonly DeletePaymentProviderCondition $deleteCondition,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', PaymentProviderCondition::class);

        $conditions = PaymentProviderCondition::query()
            ->orderBy('payment_method')
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('payment-provider-conditions/Index', [
            'conditions' => $conditions,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PaymentProviderCondition::class);

        return Inertia::render('payment-provider-conditions/Create', [
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->all(),
        ]);
    }

    public function store(StorePaymentProviderConditionRequest $request): RedirectResponse
    {
        $this->authorize('create', PaymentProviderCondition::class);

        $this->createCondition->execute($request->validated());

        return redirect()->route('payment-provider-conditions.index');
    }

    public function edit(PaymentProviderCondition $paymentProviderCondition): Response
    {
        $this->authorize('update', $paymentProviderCondition);

        return Inertia::render('payment-provider-conditions/Edit', [
            'condition' => $paymentProviderCondition,
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->all(),
        ]);
    }

    public function update(UpdatePaymentProviderConditionRequest $request, PaymentProviderCondition $paymentProviderCondition): RedirectResponse
    {
        $this->authorize('update', $paymentProviderCondition);

        $this->updateCondition->execute($paymentProviderCondition, $request->validated());

        return back();
    }

    public function destroy(PaymentProviderCondition $paymentProviderCondition): RedirectResponse
    {
        $this->authorize('delete', $paymentProviderCondition);

        $this->deleteCondition->execute($paymentProviderCondition);

        return redirect()->route('payment-provider-conditions.index');
    }
}
