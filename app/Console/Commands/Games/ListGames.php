<?php

namespace App\Console\Commands\Games;

use App\Domain\Games\Models\Game;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('games:list {--active : Show only active games} {--inactive : Show only inactive games}')]
#[Description('List all games')]
class ListGames extends Command
{
    public function handle(): int
    {
        $query = Game::query()->withCount('gameModes');

        if ($this->option('active')) {
            $query->where('is_active', true);
        } elseif ($this->option('inactive')) {
            $query->where('is_active', false);
        }

        $games = $query->orderBy('name')->get();

        if ($games->isEmpty()) {
            $this->info('No games found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Slug', 'Publisher', 'Active', 'Modes'],
            $games->map(fn (Game $game) => [
                $game->id,
                $game->name,
                $game->slug,
                $game->publisher ?? '-',
                $game->is_active ? 'Yes' : 'No',
                $game->game_modes_count,
            ]),
        );

        return self::SUCCESS;
    }
}
