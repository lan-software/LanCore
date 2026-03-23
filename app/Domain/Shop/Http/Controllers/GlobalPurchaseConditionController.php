<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Shop\Actions\CreateGlobalPurchaseCondition;
use App\Domain\Shop\Actions\DeleteGlobalPurchaseCondition;
use App\Domain\Shop\Actions\UpdateGlobalPurchaseCondition;
use App\Domain\Shop\Http\Requests\StoreGlobalPurchaseConditionRequest;
use App\Domain\Shop\Http\Requests\UpdateGlobalPurchaseConditionRequest;
use App\Domain\Shop\Models\GlobalPurchaseCondition;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GlobalPurchaseConditionController extends Controller
{
    public function __construct(
        private readonly CreateGlobalPurchaseCondition $createCondition,
        private readonly UpdateGlobalPurchaseCondition $updateCondition,
        private readonly DeleteGlobalPurchaseCondition $deleteCondition,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', GlobalPurchaseCondition::class);

        $conditions = GlobalPurchaseCondition::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('global-purchase-conditions/Index', [
            'conditions' => $conditions,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', GlobalPurchaseCondition::class);

        return Inertia::render('global-purchase-conditions/Create');
    }

    public function store(StoreGlobalPurchaseConditionRequest $request): RedirectResponse
    {
        $this->authorize('create', GlobalPurchaseCondition::class);

        $this->createCondition->execute($request->validated());

        return redirect()->route('global-purchase-conditions.index');
    }

    public function edit(GlobalPurchaseCondition $globalPurchaseCondition): Response
    {
        $this->authorize('update', $globalPurchaseCondition);

        return Inertia::render('global-purchase-conditions/Edit', [
            'condition' => $globalPurchaseCondition,
        ]);
    }

    public function update(UpdateGlobalPurchaseConditionRequest $request, GlobalPurchaseCondition $globalPurchaseCondition): RedirectResponse
    {
        $this->authorize('update', $globalPurchaseCondition);

        $this->updateCondition->execute($globalPurchaseCondition, $request->validated());

        return back();
    }

    public function destroy(GlobalPurchaseCondition $globalPurchaseCondition): RedirectResponse
    {
        $this->authorize('delete', $globalPurchaseCondition);

        $this->deleteCondition->execute($globalPurchaseCondition);

        return redirect()->route('global-purchase-conditions.index');
    }
}
