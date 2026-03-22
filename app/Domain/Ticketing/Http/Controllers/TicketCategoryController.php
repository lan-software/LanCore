<?php

namespace App\Domain\Ticketing\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Actions\CreateTicketCategory;
use App\Domain\Ticketing\Actions\DeleteTicketCategory;
use App\Domain\Ticketing\Actions\UpdateTicketCategory;
use App\Domain\Ticketing\Http\Requests\StoreTicketCategoryRequest;
use App\Domain\Ticketing\Http\Requests\TicketCategoryIndexRequest;
use App\Domain\Ticketing\Http\Requests\UpdateTicketCategoryRequest;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TicketCategoryController extends Controller
{
    public function __construct(
        private readonly CreateTicketCategory $createTicketCategory,
        private readonly UpdateTicketCategory $updateTicketCategory,
        private readonly DeleteTicketCategory $deleteTicketCategory,
    ) {}

    public function index(TicketCategoryIndexRequest $request): Response
    {
        $this->authorize('viewAny', TicketCategory::class);

        $query = TicketCategory::withCount('ticketTypes');

        $eventId = $request->validated('event_id') ?? $request->session()->get('selected_event_id');
        if ($eventId) {
            $query->where(fn ($q) => $q->where('event_id', $eventId)->orWhereNull('event_id'));
        }

        if ($search = $request->validated('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $sortColumn = $request->validated('sort') ?? 'sort_order';
        $sortDirection = $request->validated('direction') ?? 'asc';
        $query->orderBy($sortColumn, $sortDirection);

        $ticketCategories = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('ticket-categories/Index', [
            'ticketCategories' => $ticketCategories,
            'filters' => $request->only(['search', 'sort', 'direction', 'event_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TicketCategory::class);

        return Inertia::render('ticket-categories/Create', [
            'events' => Event::dropdownOptions(),
            'selectedEventId' => session('selected_event_id'),
        ]);
    }

    public function store(StoreTicketCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', TicketCategory::class);

        $this->createTicketCategory->execute($request->validated());

        return redirect()->route('ticket-categories.index');
    }

    public function edit(TicketCategory $ticketCategory): Response
    {
        $this->authorize('update', $ticketCategory);

        return Inertia::render('ticket-categories/Edit', [
            'ticketCategory' => $ticketCategory->loadCount('ticketTypes'),
            'events' => Event::dropdownOptions(),
        ]);
    }

    public function update(UpdateTicketCategoryRequest $request, TicketCategory $ticketCategory): RedirectResponse
    {
        $this->authorize('update', $ticketCategory);

        $this->updateTicketCategory->execute($ticketCategory, $request->validated());

        return back();
    }

    public function destroy(TicketCategory $ticketCategory): RedirectResponse
    {
        $this->authorize('delete', $ticketCategory);

        $this->deleteTicketCategory->execute($ticketCategory);

        return redirect()->route('ticket-categories.index');
    }
}
