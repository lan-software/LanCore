<?php

namespace App\Domain\Sponsoring\Http\Controllers;

use App\Domain\Sponsoring\Actions\CreateSponsorLevel;
use App\Domain\Sponsoring\Actions\DeleteSponsorLevel;
use App\Domain\Sponsoring\Actions\UpdateSponsorLevel;
use App\Domain\Sponsoring\Http\Requests\StoreSponsorLevelRequest;
use App\Domain\Sponsoring\Http\Requests\UpdateSponsorLevelRequest;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-SPO-002
 * @see docs/mil-std-498/SRS.md SPO-F-002, SPO-F-005
 */
class SponsorLevelController extends Controller
{
    public function __construct(
        private readonly CreateSponsorLevel $createSponsorLevel,
        private readonly UpdateSponsorLevel $updateSponsorLevel,
        private readonly DeleteSponsorLevel $deleteSponsorLevel,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', SponsorLevel::class);

        $sponsorLevels = SponsorLevel::withCount('sponsors')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('sponsor-levels/Index', [
            'sponsorLevels' => $sponsorLevels,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SponsorLevel::class);

        return Inertia::render('sponsor-levels/Create');
    }

    public function store(StoreSponsorLevelRequest $request): RedirectResponse
    {
        $this->authorize('create', SponsorLevel::class);

        $this->createSponsorLevel->execute($request->validated());

        return redirect()->route('sponsor-levels.index');
    }

    public function edit(SponsorLevel $sponsorLevel): Response
    {
        $this->authorize('update', $sponsorLevel);

        return Inertia::render('sponsor-levels/Edit', [
            'sponsorLevel' => $sponsorLevel,
        ]);
    }

    public function update(UpdateSponsorLevelRequest $request, SponsorLevel $sponsorLevel): RedirectResponse
    {
        $this->authorize('update', $sponsorLevel);

        $this->updateSponsorLevel->execute($sponsorLevel, $request->validated());

        return back();
    }

    public function destroy(SponsorLevel $sponsorLevel): RedirectResponse
    {
        $this->authorize('delete', $sponsorLevel);

        $this->deleteSponsorLevel->execute($sponsorLevel);

        return redirect()->route('sponsor-levels.index');
    }
}
