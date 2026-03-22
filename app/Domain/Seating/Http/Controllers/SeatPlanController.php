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
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhereHas('event', fn ($eq) => $eq->where('name', 'ilike', "%{$search}%"));
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
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SeatPlan::class);

        $events = Event::orderBy('name')->get(['id', 'name']);

        return Inertia::render('seating/Create', [
            'events' => $events,
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
        $events = Event::orderBy('name')->get(['id', 'name']);

        return Inertia::render('seating/Edit', [
            'seatPlan' => $seatPlan,
            'events' => $events,
        ]);
    }

    public function update(UpdateSeatPlanRequest $request, SeatPlan $seatPlan): RedirectResponse
    {
        $this->authorize('update', $seatPlan);

        $validated = $request->validated();

        if (isset($validated['data']) && is_string($validated['data'])) {
            $validated['data'] = json_decode($validated['data'], true);
        }

        $this->updateSeatPlan->execute($seatPlan, $validated);

        return back();
    }

    public function destroy(SeatPlan $seatPlan): RedirectResponse
    {
        $this->authorize('delete', $seatPlan);

        $this->deleteSeatPlan->execute($seatPlan);

        return redirect()->route('seat-plans.index');
    }
}
