<?php

namespace App\Console\Commands\Sponsoring;

use App\Domain\Sponsoring\Models\SponsorLevel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sponsoring:levels:list')]
#[Description('List all sponsor levels')]
class ListSponsorLevels extends Command
{
    public function handle(): int
    {
        $sponsorLevels = SponsorLevel::query()
            ->withCount('sponsors')
            ->orderBy('sort_order')
            ->get();

        if ($sponsorLevels->isEmpty()) {
            $this->info('No sponsor levels found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Color', 'Sort', 'Sponsors'],
            $sponsorLevels->map(fn (SponsorLevel $level) => [
                $level->id,
                $level->name,
                $level->color,
                $level->sort_order,
                $level->sponsors_count,
            ]),
        );

        return self::SUCCESS;
    }
}
