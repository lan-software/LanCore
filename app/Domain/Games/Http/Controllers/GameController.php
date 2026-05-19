<?php

namespace App\Domain\Games\Http\Controllers;

use App\Domain\Games\Actions\CreateGame;
use App\Domain\Games\Actions\DeleteGame;
use App\Domain\Games\Actions\UpdateGame;
use App\Domain\Games\Http\Requests\GameIndexRequest;
use App\Domain\Games\Http\Requests\StoreGameRequest;
use App\Domain\Games\Http\Requests\UpdateGameRequest;
use App\Domain\Games\Models\Game;
use App\Http\Controllers\Controller;
use App\Support\StorageRole;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-GAM-001
 * @see docs/mil-std-498/SRS.md GAM-F-001, GAM-F-003
 */
class GameController extends Controller
{
    public function __construct(
        private readonly CreateGame $createGame,
        private readonly UpdateGame $updateGame,
        private readonly DeleteGame $deleteGame,
    ) {}

    public function index(GameIndexRequest $request): Response
    {
        $this->authorize('viewAny', Game::class);

        $query = Game::withCount('gameModes');

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search): void {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('publisher', "%{$search}%");
            });
        }

        $sortColumn = $request->validated('sort') ?? 'name';
        $sortDirection = $request->validated('direction') ?? 'asc';
        $query->orderBy($sortColumn, $sortDirection);

        $games = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('games/Index', [
            'games' => $games,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Game::class);

        return Inertia::render('games/Create');
    }

    public function store(StoreGameRequest $request): RedirectResponse
    {
        $this->authorize('create', Game::class);

        $data = $request->safe()->except(['logo', 'banner']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('games/logos', StorageRole::publicDiskName());
        }
        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('games/banners', StorageRole::publicDiskName());
        }

        $this->createGame->execute($data);

        return redirect()->route('games.index');
    }

    public function edit(Game $game): Response
    {
        $this->authorize('update', $game);

        $game->load('gameModes');

        $gameData = $game->toArray();
        $gameData['logo_url'] = $game->logo_url;
        $gameData['banner_url'] = $game->banner_url;

        return Inertia::render('games/Edit', [
            'game' => $gameData,
        ]);
    }

    public function update(UpdateGameRequest $request, Game $game): RedirectResponse
    {
        $this->authorize('update', $game);

        $data = $request->safe()->except(['logo', 'banner', 'remove_logo', 'remove_banner']);

        if ($request->hasFile('logo')) {
            if ($game->logo_path) {
                StorageRole::public()->delete($game->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('games/logos', StorageRole::publicDiskName());
        } elseif ($request->boolean('remove_logo') && $game->logo_path) {
            StorageRole::public()->delete($game->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('banner')) {
            if ($game->banner_path) {
                StorageRole::public()->delete($game->banner_path);
            }
            $data['banner_path'] = $request->file('banner')->store('games/banners', StorageRole::publicDiskName());
        } elseif ($request->boolean('remove_banner') && $game->banner_path) {
            StorageRole::public()->delete($game->banner_path);
            $data['banner_path'] = null;
        }

        $this->updateGame->execute($game, $data);

        return back();
    }

    public function destroy(Game $game): RedirectResponse
    {
        $this->authorize('delete', $game);

        $this->deleteGame->execute($game);

        return redirect()->route('games.index');
    }
}
