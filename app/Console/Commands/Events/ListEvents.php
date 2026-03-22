<?php

namespace App\Console\Commands\Events;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('events:list {--status= : Filter by status (draft, published)} {--venue= : Filter by venue ID}')]
#[Description('List all events')]
class ListEvents extends Command
{
    public function handle(): int
    {
        $query = Event::query()->with(['venue']);

        if ($this->option('status') !== null) {
            $status = EventStatus::tryFrom($this->option('status'));

            if (! $status) {
                $this->error("Invalid status '{$this->option('status')}'. Valid statuses: ".implode(', ', array_column(EventStatus::cases(), 'value')));

                return self::FAILURE;
            }

            $query->where('status', $status);
        }

        if ($this->option('venue') !== null) {
            $query->where('venue_id', $this->option('venue'));
        }

        $events = $query->orderBy('start_date', 'desc')->get();

        if ($events->isEmpty()) {
            $this->info('No events found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Status', 'Venue', 'Start', 'End', 'Seats'],
            $events->map(fn (Event $event) => [
                $event->id,
                $event->name,
                $event->status->value,
                $event->venue?->name ?? '-',
                $event->start_date?->format('Y-m-d H:i') ?? '-',
                $event->end_date?->format('Y-m-d H:i') ?? '-',
                $event->seat_capacity ?? '-',
            ]),
        );

        return self::SUCCESS;
    }
}
