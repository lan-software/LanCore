<?php

namespace App\Domain\Ticketing\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Actions\CreateTicketType;
use App\Domain\Ticketing\Actions\DeleteTicketType;
use App\Domain\Ticketing\Actions\UpdateTicketType;
use App\Domain\Ticketing\Http\Requests\StoreTicketTypeRequest;
use App\Domain\Ticketing\Http\Requests\TicketTypeIndexRequest;
use App\Domain\Ticketing\Http\Requests\UpdateTicketTypeRequest;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Domain\Ticketing\Models\TicketGroup;
use App\Domain\Ticketing\Models\TicketType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TicketTypeController extends Controller
{
    public function __construct(
        private readonly CreateTicketType $createTicketType,
        private readonly UpdateTicketType $updateTicketType,
        private readonly DeleteTicketType $deleteTicketType,
    ) {}

    public function index(TicketTypeIndexRequest $request): Response
    {
        $this->authorize('viewAny', TicketType::class);

        $query = TicketType::with(['event', 'ticketCategory', 'ticketGroup'])
            ->withCount('tickets');

        if ($search = $request->validated('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($eventId = $request->validated('event_id')) {
            $query->where('event_id', $eventId);
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $ticketTypes = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('ticket-types/Index', [
            'ticketTypes' => $ticketTypes,
            'events' => Event::dropdownOptions(),
            'filters' => $request->only(['search', 'sort', 'direction', 'event_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TicketType::class);

        return Inertia::render('ticket-types/Create', [
            'events' => Event::dropdownOptions(),
            'categories' => TicketCategory::dropdownOptions(),
            'groups' => TicketGroup::dropdownOptions(),
        ]);
    }

    public function store(StoreTicketTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', TicketType::class);

        $this->createTicketType->execute($request->validated());

        return redirect()->route('ticket-types.index');
    }

    public function edit(TicketType $ticketType): Response
    {
        $this->authorize('update', $ticketType);

        return Inertia::render('ticket-types/Edit', [
            'ticketType' => $ticketType->load(['event', 'ticketCategory', 'ticketGroup'])->loadCount('tickets'),
            'events' => Event::dropdownOptions(),
            'categories' => TicketCategory::dropdownOptions(),
            'groups' => TicketGroup::dropdownOptions(),
        ]);
    }

    public function update(UpdateTicketTypeRequest $request, TicketType $ticketType): RedirectResponse
    {
        $this->authorize('update', $ticketType);

        $this->updateTicketType->execute($ticketType, $request->validated());

        return back();
    }

    public function destroy(TicketType $ticketType): RedirectResponse
    {
        $this->authorize('delete', $ticketType);

        $this->deleteTicketType->execute($ticketType);

        return redirect()->route('ticket-types.index');
    }
}
