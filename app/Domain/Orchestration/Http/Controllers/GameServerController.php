<?php

namespace App\Domain\Orchestration\Http\Controllers;

use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\Orchestration\Actions\CreateGameServer;
use App\Domain\Orchestration\Actions\DeleteGameServer;
use App\Domain\Orchestration\Actions\ForceReleaseGameServer;
use App\Domain\Orchestration\Actions\UpdateGameServer;
use App\Domain\Orchestration\Http\Requests\StoreGameServerRequest;
use App\Domain\Orchestration\Http\Requests\UpdateGameServerRequest;
use App\Domain\Orchestration\Models\GameServer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-001, ORC-F-003
 */
class GameServerController extends Controller
{
    public function __construct(
        private readonly CreateGameServer $createGameServer,
        private readonly UpdateGameServer $updateGameServer,
        private readonly DeleteGameServer $deleteGameServer,
        private readonly ForceReleaseGameServer $forceReleaseGameServer,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', GameServer::class);

        $query = GameServer::with('game', 'gameMode', 'activeOrchestrationJob');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('host', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($gameId = $request->input('game_id')) {
            $query->where('game_id', $gameId);
        }

        $sortColumn = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy($sortColumn, $sortDirection);

        $servers = $query->paginate($request->input('per_page', 20))->withQueryString();

        return Inertia::render('orchestration/servers/Index', [
            'servers' => $servers,
            'filters' => $request->only(['search', 'status', 'game_id', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', GameServer::class);

        return Inertia::render('orchestration/servers/Create', [
            'games' => Game::where('is_active', true)->get(),
            'gameModes' => GameMode::where('is_active', true)->get(),
        ]);
    }

    public function store(StoreGameServerRequest $request): RedirectResponse
    {
        $this->authorize('create', GameServer::class);

        $this->createGameServer->execute($request->validated());

        return redirect()->route('game-servers.index');
    }

    public function edit(GameServer $gameServer): Response
    {
        $this->authorize('update', $gameServer);

        $gameServer->load('game', 'gameMode', 'activeOrchestrationJob');

        return Inertia::render('orchestration/servers/Edit', [
            'server' => $gameServer,
            'games' => Game::where('is_active', true)->get(),
            'gameModes' => GameMode::where('is_active', true)->get(),
        ]);
    }

    public function update(UpdateGameServerRequest $request, GameServer $gameServer): RedirectResponse
    {
        $this->authorize('update', $gameServer);

        $this->updateGameServer->execute($gameServer, $request->validated());

        return back();
    }

    public function destroy(GameServer $gameServer): RedirectResponse
    {
        $this->authorize('delete', $gameServer);

        $this->deleteGameServer->execute($gameServer);

        return redirect()->route('game-servers.index');
    }

    public function forceRelease(GameServer $gameServer): RedirectResponse
    {
        $this->authorize('update', $gameServer);

        $this->forceReleaseGameServer->execute($gameServer);

        return back();
    }
}
