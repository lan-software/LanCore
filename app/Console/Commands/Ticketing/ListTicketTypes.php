<?php

namespace App\Console\Commands\Ticketing;

use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ticketing:types:list {--event= : Filter by event ID} {--locked : Show only locked types} {--hidden : Show only hidden types}')]
#[Description('List all ticket types')]
class ListTicketTypes extends Command
{
    public function handle(): int
    {
        $query = TicketType::query()->with('event')->withCount('tickets');

        if ($this->option('event') !== null) {
            $query->where('event_id', $this->option('event'));
        }

        if ($this->option('locked')) {
            $query->where('is_locked', true);
        }

        if ($this->option('hidden')) {
            $query->where('is_hidden', true);
        }

        $ticketTypes = $query->orderBy('id')->get();

        if ($ticketTypes->isEmpty()) {
            $this->info('No ticket types found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Event', 'Price', 'Quota', 'Sold', 'Locked', 'Hidden'],
            $ticketTypes->map(fn (TicketType $type) => [
                $type->id,
                $type->name,
                $type->event?->name ?? '-',
                number_format($type->price / 100, 2),
                $type->quota ?? '∞',
                $type->tickets_count,
                $type->is_locked ? 'Yes' : 'No',
                $type->is_hidden ? 'Yes' : 'No',
            ]),
        );

        return self::SUCCESS;
    }
}
