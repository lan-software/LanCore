<?php

namespace App\Domain\Orchestration\Contracts;

use App\Domain\Games\Models\Game;
use App\Domain\Orchestration\Models\GameServer;

/**
 * Contract for game-specific match handlers.
 *
 * Implementations translate generic match configs into game-specific
 * server configurations and deploy them via their respective protocols.
 *
 * @see docs/mil-std-498/SRS.md ORC-F-008
 */
interface MatchHandlerContract
{
    /**
     * Whether this handler supports the given game.
     */
    public function supports(Game $game): bool;

    /**
     * Deploy a match configuration to the given game server.
     *
     * @param  array<string, mixed>  $matchConfig
     */
    public function deploy(GameServer $server, array $matchConfig): void;

    /**
     * Tear down / clean up after a match completes on the server.
     *
     * @param  array<string, mixed>  $matchConfig
     */
    public function teardown(GameServer $server, array $matchConfig): void;

    /**
     * Check if the game server is reachable and ready to accept deployments.
     */
    public function healthCheck(GameServer $server): bool;
}
