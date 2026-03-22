<?php

namespace App\Console\Commands\Sponsoring;

use App\Domain\Sponsoring\Models\Sponsor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sponsoring:list {--level= : Filter by sponsor level ID}')]
#[Description('List all sponsors')]
class ListSponsors extends Command
{
    public function handle(): int
    {
        $query = Sponsor::query()->with('sponsorLevel');

        if ($this->option('level') !== null) {
            $query->where('sponsor_level_id', $this->option('level'));
        }

        $sponsors = $query->orderBy('name')->get();

        if ($sponsors->isEmpty()) {
            $this->info('No sponsors found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Level', 'Link'],
            $sponsors->map(fn (Sponsor $sponsor) => [
                $sponsor->id,
                $sponsor->name,
                $sponsor->sponsorLevel?->name ?? '-',
                $sponsor->link ?? '-',
            ]),
        );

        return self::SUCCESS;
    }
}
