<?php

namespace App\Console\Commands\Ticketing;

use App\Domain\Ticketing\Models\Addon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ticketing:addons:list {--event= : Filter by event ID} {--hidden : Show only hidden addons}')]
#[Description('List all ticket addons')]
class ListAddons extends Command
{
    public function handle(): int
    {
        $query = Addon::query()->with('event');

        if ($this->option('event') !== null) {
            $query->where('event_id', $this->option('event'));
        }

        if ($this->option('hidden')) {
            $query->where('is_hidden', true);
        }

        $addons = $query->orderBy('id')->get();

        if ($addons->isEmpty()) {
            $this->info('No ticket addons found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Event', 'Price', 'Quota', 'Requires Ticket', 'Hidden'],
            $addons->map(fn (Addon $addon) => [
                $addon->id,
                $addon->name,
                $addon->event?->name ?? '-',
                number_format($addon->price / 100, 2),
                $addon->quota ?? '∞',
                $addon->requires_ticket ? 'Yes' : 'No',
                $addon->is_hidden ? 'Yes' : 'No',
            ]),
        );

        return self::SUCCESS;
    }
}
