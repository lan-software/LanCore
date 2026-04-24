<?php

namespace App\Domain\Seating\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Actions\CreateSeatPlan;
use App\Domain\Seating\Actions\DeleteSeatPlan;
use App\Domain\Seating\Actions\UpdateSeatPlan;
use App\Domain\Seating\Http\Requests\SeatPlanIndexRequest;
use App\Domain\Seating\Http\Requests\StoreSeatPlanRequest;
use App\Domain\Seating\Http\Requests\UpdateSeatPlanRequest;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-SET-001, CAP-SET-003
 * @see docs/mil-std-498/SRS.md SET-F-001, SET-F-003, SET-F-004
 */
class SeatPlanController extends Controller
{
    public function __construct(
        private readonly CreateSeatPlan $createSeatPlan,
        private readonly UpdateSeatPlan $updateSeatPlan,
        private readonly DeleteSeatPlan $deleteSeatPlan,
    ) {}

    public function index(SeatPlanIndexRequest $request): Response
    {
        $this->authorize('viewAny', SeatPlan::class);

        $query = SeatPlan::with('event:id,name');

        $eventId = $request->validated('event_id') ?? $request->session()->get('selected_event_id');
        if ($eventId) {
            $query->where(fn ($q) => $q->where('event_id', $eventId)->orWhereNull('event_id'));
        }

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search): void {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereHas('event', fn ($eq) => $eq->whereLike('name', "%{$search}%"));
            });
        }

        $sortColumn = $request->validated('sort') ?? 'name';
        $sortDirection = $request->validated('direction') ?? 'asc';

        if ($sortColumn === 'event_name') {
            $query->join('events', 'seat_plans.event_id', '=', 'events.id')
                ->orderBy('events.name', $sortDirection)
                ->select('seat_plans.*');
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $seatPlans = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('seating/Index', [
            'seatPlans' => $seatPlans,
            'filters' => $request->only(['search', 'sort', 'direction', 'event_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SeatPlan::class);

        return Inertia::render('seating/Create', [
            'events' => Event::dropdownOptions(),
            'selectedEventId' => session('selected_event_id'),
        ]);
    }

    public function store(StoreSeatPlanRequest $request): RedirectResponse
    {
        $this->authorize('create', SeatPlan::class);

        $validated = $request->validated();

        if (isset($validated['data']) && is_string($validated['data'])) {
            $validated['data'] = json_decode($validated['data'], true);
        }

        $this->createSeatPlan->execute($validated);

        return redirect()->route('seat-plans.index');
    }

    public function edit(SeatPlan $seatPlan): Response
    {
        $this->authorize('update', $seatPlan);

        $seatPlan->load('event:id,name');

        // Ticket categories for the plan's event drive the per-block restriction
        // editor (SET-F-011). Scoped to event only — a plan never needs
        // categories from a different event.
        $ticketCategories = TicketCategory::query()
            ->where('event_id', $seatPlan->event_id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'sort_order']);

        return Inertia::render('seating/Edit', [
            'seatPlan' => $seatPlan,
            'events' => Event::dropdownOptions(),
            'ticketCategories' => $ticketCategories,
        ]);
    }

    public function update(UpdateSeatPlanRequest $request, SeatPlan $seatPlan): RedirectResponse
    {
        $this->authorize('update', $seatPlan);

        $validated = $request->validated();

        if (isset($validated['data']) && is_string($validated['data'])) {
            $validated['data'] = json_decode($validated['data'], true);
        }

        $confirmInvalidations = (bool) ($validated['confirm_invalidations'] ?? false);
        unset($validated['confirm_invalidations']);

        $result = $this->updateSeatPlan->execute($seatPlan, $validated, $confirmInvalidations);

        if ($result->needsConfirmation()) {
            // Two-phase save: first call reports what WOULD be invalidated; no
            // DB write has happened. The Edit.vue dialog re-submits with
            // confirm_invalidations=true once the admin acknowledges.
            return back()
                ->with('invalidations', $result->invalidations->values()->all())
                ->withInput($request->all());
        }

        return back()->with('status', 'seat-plan-updated');
    }

    public function destroy(SeatPlan $seatPlan): RedirectResponse
    {
        $this->authorize('delete', $seatPlan);

        $this->deleteSeatPlan->execute($seatPlan);

        return redirect()->route('seat-plans.index');
    }
}
