<?php

use App\Domain\Games\Models\Game;

it('lists all games in a table', function () {
    $game = Game::factory()->create(['name' => 'Counter-Strike', 'is_active' => true]);

    $this->artisan('games:list')
        ->expectsTable(
            ['ID', 'Name', 'Slug', 'Publisher', 'Active', 'Modes'],
            [
                [
                    $game->id,
                    'Counter-Strike',
                    $game->slug,
                    $game->publisher ?? '-',
                    'Yes',
                    0,
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters active games', function () {
    Game::factory()->create(['name' => 'Active Game', 'is_active' => true]);
    Game::factory()->create(['name' => 'Inactive Game', 'is_active' => false]);

    $this->artisan('games:list --active')
        ->expectsOutputToContain('Active Game')
        ->assertSuccessful();
});

it('filters inactive games', function () {
    Game::factory()->create(['name' => 'Active Game', 'is_active' => true]);
    Game::factory()->create(['name' => 'Inactive Game', 'is_active' => false]);

    $this->artisan('games:list --inactive')
        ->expectsOutputToContain('Inactive Game')
        ->assertSuccessful();
});

it('shows message when no games found', function () {
    $this->artisan('games:list')
        ->expectsOutputToContain('No games found.')
        ->assertSuccessful();
});
