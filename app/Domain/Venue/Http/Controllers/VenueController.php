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
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-EVT-003
 * @see docs/mil-std-498/SRS.md EVT-F-006, EVT-F-007
 */
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

        $images = [];
        foreach ($request->validated('images', []) as $imageData) {
            $path = $imageData['file']->store('venues/images');
            $images[] = [
                'path' => $path,
                'alt_text' => $imageData['alt_text'] ?? null,
            ];
        }

        $this->createVenue->execute(
            $request->safe()->only(['name', 'description', 'street', 'city', 'zip_code', 'state', 'country']),
            $images,
        );

        return redirect()->route('venues.index');
    }

    public function edit(Venue $venue): Response
    {
        $this->authorize('update', $venue);

        $venueData = $venue->load(['address', 'images'])->toArray();
        $venueData['images'] = collect($venueData['images'])->map(function (array $image) {
            $image['url'] = Storage::url($image['path']);

            return $image;
        })->all();

        return Inertia::render('venues/Edit', [
            'venue' => $venueData,
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $this->authorize('update', $venue);

        $existingImages = $request->validated('existing_images', []);
        $newImages = [];

        foreach ($request->validated('new_images', []) as $imageData) {
            $path = $imageData['file']->store('venues/images');
            $newImages[] = [
                'path' => $path,
                'alt_text' => $imageData['alt_text'] ?? null,
            ];
        }

        $this->updateVenue->execute(
            $venue,
            $request->safe()->only(['name', 'description', 'street', 'city', 'zip_code', 'state', 'country']),
            $existingImages,
            $newImages,
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
