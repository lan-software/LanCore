<?php

namespace App\Domain\Sponsoring\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Sponsoring\Actions\CreateSponsor;
use App\Domain\Sponsoring\Actions\DeleteSponsor;
use App\Domain\Sponsoring\Actions\UpdateSponsor;
use App\Domain\Sponsoring\Enums\Permission;
use App\Domain\Sponsoring\Http\Requests\SponsorIndexRequest;
use App\Domain\Sponsoring\Http\Requests\StoreSponsorRequest;
use App\Domain\Sponsoring\Http\Requests\UpdateSponsorRequest;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-SPO-001, CAP-SPO-003
 * @see docs/mil-std-498/SRS.md SPO-F-001, SPO-F-003, SPO-F-005
 */
class SponsorController extends Controller
{
    public function __construct(
        private readonly CreateSponsor $createSponsor,
        private readonly UpdateSponsor $updateSponsor,
        private readonly DeleteSponsor $deleteSponsor,
    ) {}

    public function index(SponsorIndexRequest $request): Response
    {
        $this->authorize('viewAny', Sponsor::class);

        $user = $request->user();
        $query = Sponsor::with(['sponsorLevel', 'events']);

        if ($user->hasPermission(Permission::ManageAssignedSponsors) && ! $user->hasPermission(Permission::ManageSponsors)) {
            $query->whereHas('managers', fn ($q) => $q->where('user_id', $user->id));
        }

        $eventId = $request->validated('event_id') ?? $request->session()->get('selected_event_id');
        if ($eventId) {
            $query->where(fn ($q) => $q->whereHas('events', fn ($eq) => $eq->where('events.id', $eventId))->orDoesntHave('events'));
        }

        if ($search = $request->validated('search')) {
            $query->whereLike('name', "%{$search}%");
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $sponsors = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('sponsors/Index', [
            'sponsors' => $sponsors,
            'filters' => $request->only(['search', 'sort', 'direction', 'event_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Sponsor::class);

        return Inertia::render('sponsors/Create', [
            'sponsorLevels' => SponsorLevel::dropdownOptions(),
            'events' => Event::dropdownOptions(),
        ]);
    }

    public function store(StoreSponsorRequest $request): RedirectResponse
    {
        $this->authorize('create', Sponsor::class);

        $data = $request->safe()->except(['logo', 'event_ids']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('sponsors/logos');
        }

        $this->createSponsor->execute($data, $request->validated('event_ids', []));

        return redirect()->route('sponsors.index');
    }

    public function edit(Sponsor $sponsor): Response
    {
        $this->authorize('update', $sponsor);

        $sponsor->load(['sponsorLevel', 'events', 'managers']);

        $sponsorData = $sponsor->toArray();
        $sponsorData['logo_url'] = $sponsor->logo ? Storage::url($sponsor->logo) : null;

        return Inertia::render('sponsors/Edit', [
            'sponsor' => $sponsorData,
            'sponsorLevels' => SponsorLevel::dropdownOptions(),
            'events' => Event::dropdownOptions(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(UpdateSponsorRequest $request, Sponsor $sponsor): RedirectResponse
    {
        $this->authorize('update', $sponsor);

        $data = $request->safe()->except(['logo', 'remove_logo', 'event_ids', 'manager_ids']);

        if ($request->hasFile('logo')) {
            if ($sponsor->logo) {
                Storage::delete($sponsor->logo);
            }
            $data['logo'] = $request->file('logo')->store('sponsors/logos');
        } elseif ($request->boolean('remove_logo')) {
            if ($sponsor->logo) {
                Storage::delete($sponsor->logo);
            }
            $data['logo'] = null;
        }

        $eventIds = $request->user()->hasPermission(Permission::ManageSponsors) ? $request->validated('event_ids', []) : null;
        $managerIds = $request->user()->hasPermission(Permission::ManageSponsors) ? $request->validated('manager_ids', []) : null;

        $this->updateSponsor->execute($sponsor, $data, $eventIds, $managerIds);

        return back();
    }

    public function destroy(Sponsor $sponsor): RedirectResponse
    {
        $this->authorize('delete', $sponsor);

        $this->deleteSponsor->execute($sponsor);

        return redirect()->route('sponsors.index');
    }
}
