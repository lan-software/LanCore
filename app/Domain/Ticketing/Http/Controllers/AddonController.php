<?php

namespace App\Domain\Ticketing\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Actions\CreateAddon;
use App\Domain\Ticketing\Actions\DeleteAddon;
use App\Domain\Ticketing\Actions\UpdateAddon;
use App\Domain\Ticketing\Http\Requests\AddonIndexRequest;
use App\Domain\Ticketing\Http\Requests\StoreAddonRequest;
use App\Domain\Ticketing\Http\Requests\UpdateAddonRequest;
use App\Domain\Ticketing\Models\Addon;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AddonController extends Controller
{
    public function __construct(
        private readonly CreateAddon $createAddon,
        private readonly UpdateAddon $updateAddon,
        private readonly DeleteAddon $deleteAddon,
    ) {}

    public function index(AddonIndexRequest $request): Response
    {
        $this->authorize('viewAny', Addon::class);

        $query = Addon::with('event')
            ->withCount('tickets');

        if ($search = $request->validated('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $eventId = $request->validated('event_id') ?? $request->session()->get('selected_event_id');
        if ($eventId) {
            $query->where(fn ($q) => $q->where('event_id', $eventId)->orWhereNull('event_id'));
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $addons = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('ticket-addons/Index', [
            'ticketAddons' => $addons,
            'events' => Event::dropdownOptions(),
            'filters' => $request->only(['search', 'sort', 'direction', 'event_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Addon::class);

        return Inertia::render('ticket-addons/Create', [
            'events' => Event::dropdownOptions(),
            'selectedEventId' => session('selected_event_id'),
        ]);
    }

    public function store(StoreAddonRequest $request): RedirectResponse
    {
        $this->authorize('create', Addon::class);

        $this->createAddon->execute($request->validated());

        return redirect()->route('ticket-addons.index');
    }

    public function edit(Addon $ticketAddon): Response
    {
        $this->authorize('update', $ticketAddon);

        return Inertia::render('ticket-addons/Edit', [
            'ticketAddon' => $ticketAddon->load('event')->loadCount('tickets'),
            'events' => Event::dropdownOptions(),
        ]);
    }

    public function update(UpdateAddonRequest $request, Addon $ticketAddon): RedirectResponse
    {
        $this->authorize('update', $ticketAddon);

        $this->updateAddon->execute($ticketAddon, $request->validated());

        return back();
    }

    public function destroy(Addon $ticketAddon): RedirectResponse
    {
        $this->authorize('delete', $ticketAddon);

        $this->deleteAddon->execute($ticketAddon);

        return redirect()->route('ticket-addons.index');
    }
}
