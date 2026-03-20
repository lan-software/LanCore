<?php

namespace App\Domain\Venue\Http\Controllers;

use App\Domain\Venue\Actions\CreateVenue;
use App\Domain\Venue\Actions\DeleteVenue;
use App\Domain\Venue\Actions\UpdateVenue;
use App\Domain\Venue\Http\Requests\StoreVenueRequest;
use App\Domain\Venue\Http\Requests\UpdateVenueRequest;
use App\Domain\Venue\Http\Requests\VenueIndexRequest;
use App\Domain\Venue\Models\Venue;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function __construct(
        private readonly CreateVenue $createVenue,
        private readonly UpdateVenue $updateVenue,
        private readonly DeleteVenue $deleteVenue,
    ) {}

    public function index(VenueIndexRequest $request): Response
    {
        $this->authorize('viewAny', Venue::class);

        $query = Venue::with('address');

        if ($search = $request->validated('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $sortColumn = $request->validated('sort') ?? 'name';
        $sortDirection = $request->validated('direction') ?? 'asc';
        $query->orderBy($sortColumn, $sortDirection);

        $venues = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('venues/Index', [
            'venues' => $venues,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Venue::class);

        return Inertia::render('venues/Create');
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        $this->authorize('create', Venue::class);

        $this->createVenue->execute(
            $request->safe()->only(['name', 'description', 'street', 'city', 'zip_code', 'state', 'country']),
            $request->validated('images', []),
        );

        return redirect()->route('venues.index');
    }

    public function edit(Venue $venue): Response
    {
        $this->authorize('update', $venue);

        return Inertia::render('venues/Edit', [
            'venue' => $venue->load(['address', 'images']),
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $this->authorize('update', $venue);

        $this->updateVenue->execute(
            $venue,
            $request->safe()->only(['name', 'description', 'street', 'city', 'zip_code', 'state', 'country']),
            $request->validated('images', []),
        );

        return back();
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $this->authorize('delete', $venue);

        $this->deleteVenue->execute($venue);

        return redirect()->route('venues.index');
    }
}
