<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Shop\Actions\CreatePurchaseRequirement;
use App\Domain\Shop\Actions\DeletePurchaseRequirement;
use App\Domain\Shop\Actions\UpdatePurchaseRequirement;
use App\Domain\Shop\Http\Requests\StorePurchaseRequirementRequest;
use App\Domain\Shop\Http\Requests\UpdatePurchaseRequirementRequest;
use App\Domain\Shop\Models\PurchaseRequirement;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRequirementController extends Controller
{
    public function __construct(
        private readonly CreatePurchaseRequirement $createPurchaseRequirement,
        private readonly UpdatePurchaseRequirement $updatePurchaseRequirement,
        private readonly DeletePurchaseRequirement $deletePurchaseRequirement,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', PurchaseRequirement::class);

        $requirements = PurchaseRequirement::query()
            ->withCount(['ticketTypes', 'addons'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('purchase-requirements/Index', [
            'requirements' => $requirements,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PurchaseRequirement::class);

        return Inertia::render('purchase-requirements/Create', [
            'ticketTypes' => TicketType::select('id', 'name')->orderBy('name')->get(),
            'addons' => Addon::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(StorePurchaseRequirementRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseRequirement::class);

        $validated = $request->validated();
        $ticketTypeIds = $validated['ticket_type_ids'] ?? [];
        $addonIds = $validated['addon_ids'] ?? [];
        unset($validated['ticket_type_ids'], $validated['addon_ids']);

        $this->createPurchaseRequirement->execute($validated, $ticketTypeIds, $addonIds);

        return redirect()->route('purchase-requirements.index');
    }

    public function edit(PurchaseRequirement $purchaseRequirement): Response
    {
        $this->authorize('update', $purchaseRequirement);

        return Inertia::render('purchase-requirements/Edit', [
            'requirement' => $purchaseRequirement->load(['ticketTypes:id,name', 'addons:id,name']),
            'ticketTypes' => TicketType::select('id', 'name')->orderBy('name')->get(),
            'addons' => Addon::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdatePurchaseRequirementRequest $request, PurchaseRequirement $purchaseRequirement): RedirectResponse
    {
        $this->authorize('update', $purchaseRequirement);

        $validated = $request->validated();
        $ticketTypeIds = $validated['ticket_type_ids'] ?? [];
        $addonIds = $validated['addon_ids'] ?? [];
        unset($validated['ticket_type_ids'], $validated['addon_ids']);

        $this->updatePurchaseRequirement->execute($purchaseRequirement, $validated, $ticketTypeIds, $addonIds);

        return back();
    }

    public function destroy(PurchaseRequirement $purchaseRequirement): RedirectResponse
    {
        $this->authorize('delete', $purchaseRequirement);

        $this->deletePurchaseRequirement->execute($purchaseRequirement);

        return redirect()->route('purchase-requirements.index');
    }
}
