<?php

namespace App\Domain\Achievements\Http\Controllers;

use App\Domain\Achievements\Actions\CreateAchievement;
use App\Domain\Achievements\Actions\DeleteAchievement;
use App\Domain\Achievements\Actions\UpdateAchievement;
use App\Domain\Achievements\Enums\GrantableEvent;
use App\Domain\Achievements\Http\Requests\StoreAchievementRequest;
use App\Domain\Achievements\Http\Requests\UpdateAchievementRequest;
use App\Domain\Achievements\Models\Achievement;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-ACH-001
 * @see docs/mil-std-498/SRS.md ACH-F-001, ACH-F-005
 */
class AchievementController extends Controller
{
    public function __construct(
        private readonly CreateAchievement $createAchievement,
        private readonly UpdateAchievement $updateAchievement,
        private readonly DeleteAchievement $deleteAchievement,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Achievement::class);

        $query = Achievement::withCount('users');

        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $sortColumn = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortColumn, $sortDirection);

        $achievements = $query->paginate($request->input('per_page', 20))->withQueryString();

        return Inertia::render('achievements/Index', [
            'achievements' => $achievements,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Achievement::class);

        return Inertia::render('achievements/Create', [
            'grantableEvents' => collect(GrantableEvent::cases())->map(fn (GrantableEvent $e) => [
                'value' => $e->value,
                'label' => $e->label(),
            ])->all(),
        ]);
    }

    public function store(StoreAchievementRequest $request): RedirectResponse
    {
        $this->authorize('create', Achievement::class);

        $attributes = $request->safe()->except(['event_classes']);

        $this->createAchievement->execute(
            $attributes,
            $request->validated('event_classes', []),
        );

        return redirect()->route('achievements.index');
    }

    public function edit(Achievement $achievement): Response
    {
        $this->authorize('update', $achievement);

        $achievement->load('achievementEvents');

        return Inertia::render('achievements/Edit', [
            'achievement' => $achievement->toArray() + [
                'event_classes' => $achievement->achievementEvents->pluck('event_class')->all(),
            ],
            'grantableEvents' => collect(GrantableEvent::cases())->map(fn (GrantableEvent $e) => [
                'value' => $e->value,
                'label' => $e->label(),
            ])->all(),
        ]);
    }

    public function update(UpdateAchievementRequest $request, Achievement $achievement): RedirectResponse
    {
        $this->authorize('update', $achievement);

        $attributes = $request->safe()->except(['event_classes']);

        $this->updateAchievement->execute(
            $achievement,
            $attributes,
            $request->validated('event_classes', []),
        );

        return back();
    }

    public function destroy(Achievement $achievement): RedirectResponse
    {
        $this->authorize('delete', $achievement);

        $this->deleteAchievement->execute($achievement);

        return redirect()->route('achievements.index');
    }
}
