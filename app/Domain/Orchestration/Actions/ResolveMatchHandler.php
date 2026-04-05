<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Games\Models\Game;
use App\Domain\Orchestration\Contracts\MatchHandlerContract;
use RuntimeException;

/**
 * Resolves the appropriate MatchHandler implementation for a game.
 *
 * @see docs/mil-std-498/SRS.md ORC-F-008
 */
class ResolveMatchHandler
{
    /**
     * @param  iterable<MatchHandlerContract>  $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {}

    public function execute(Game $game): MatchHandlerContract
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($game)) {
                return $handler;
            }
        }

        throw new RuntimeException("No match handler found for game: {$game->name}");
    }
}
