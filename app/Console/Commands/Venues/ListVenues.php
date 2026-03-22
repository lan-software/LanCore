<?php

namespace App\Console\Commands\Venues;

use App\Domain\Venue\Models\Venue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('venue:list {--city= : Filter by city}')]
#[Description('List all venues')]
class ListVenues extends Command
{
    public function handle(): int
    {
        $query = Venue::query()->with('address');

        if ($this->option('city') !== null) {
            $query->whereHas('address', fn ($q) => $q->where('city', 'like', '%'.$this->option('city').'%'));
        }

        $venues = $query->orderBy('name')->get();

        if ($venues->isEmpty()) {
            $this->info('No venues found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'City', 'Country'],
            $venues->map(fn (Venue $venue) => [
                $venue->id,
                $venue->name,
                $venue->address?->city ?? '-',
                $venue->address?->country ?? '-',
            ]),
        );

        return self::SUCCESS;
    }
}
