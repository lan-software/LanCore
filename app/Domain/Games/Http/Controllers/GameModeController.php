<?php

namespace App\Domain\Games\Http\Controllers;

use App\Domain\Games\Actions\CreateGameMode;
use App\Domain\Games\Actions\DeleteGameMode;
use App\Domain\Games\Actions\UpdateGameMode;
use App\Domain\Games\Http\Requests\StoreGameModeRequest;
use App\Domain\Games\Http\Requests\UpdateGameModeRequest;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GameModeController extends Controller
{
    public function __construct(
        private readonly CreateGameMode $createGameMode,
        private readonly UpdateGameMode $updateGameMode,
        private readonly DeleteGameMode $deleteGameMode,
    ) {}

    public function create(Game $game): Response
    {
        $this->authorize('create', GameMode::class);

        return Inertia::render('games/modes/Create', [
            'game' => $game,
        ]);
    }

    public function store(StoreGameModeRequest $request, Game $game): RedirectResponse
    {
        $this->authorize('create', GameMode::class);

        $validated = $request->validated();

        if (isset($validated['parameters']) && is_string($validated['parameters'])) {
            $validated['parameters'] = json_decode($validated['parameters'], true);
        }

        $this->createGameMode->execute($game, $validated);

        return redirect()->route('games.edit', $game);
    }

    public function edit(Game $game, GameMode $mode): Response
    {
        $this->authorize('update', $mode);

        return Inertia::render('games/modes/Edit', [
            'game' => $game,
            'gameMode' => $mode,
        ]);
    }

    public function update(UpdateGameModeRequest $request, Game $game, GameMode $mode): RedirectResponse
    {
        $this->authorize('update', $mode);

        $validated = $request->validated();

        if (isset($validated['parameters']) && is_string($validated['parameters'])) {
            $validated['parameters'] = json_decode($validated['parameters'], true);
        }

        $this->updateGameMode->execute($mode, $validated);

        return back();
    }

    public function destroy(Game $game, GameMode $mode): RedirectResponse
    {
        $this->authorize('delete', $mode);

        $this->deleteGameMode->execute($mode);

        return redirect()->route('games.edit', $game);
    }
}
