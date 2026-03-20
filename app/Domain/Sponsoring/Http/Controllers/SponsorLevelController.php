<?php

namespace App\Domain\Sponsoring\Http\Controllers;

use App\Domain\Sponsoring\Http\Requests\StoreSponsorLevelRequest;
use App\Domain\Sponsoring\Http\Requests\UpdateSponsorLevelRequest;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SponsorLevelController extends Controller
{
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

        $maxOrder = SponsorLevel::max('sort_order') ?? -1;

        SponsorLevel::create([
            ...$request->validated(),
            'sort_order' => $maxOrder + 1,
        ]);

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

        $sponsorLevel->fill($request->validated())->save();

        return back();
    }

    public function destroy(SponsorLevel $sponsorLevel): RedirectResponse
    {
        $this->authorize('delete', $sponsorLevel);

        $sponsorLevel->delete();

        return redirect()->route('sponsor-levels.index');
    }
}
