<?php

namespace App\Console\Commands\Seating;

use App\Domain\Seating\Models\SeatPlan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('seating:list {--event= : Filter by event ID}')]
#[Description('List all seat plans')]
class ListSeatPlans extends Command
{
    public function handle(): int
    {
        $query = SeatPlan::query()->with('event');

        if ($this->option('event') !== null) {
            $query->where('event_id', $this->option('event'));
        }

        $seatPlans = $query->orderBy('id')->get();

        if ($seatPlans->isEmpty()) {
            $this->info('No seat plans found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Event', 'Blocks'],
            $seatPlans->map(fn (SeatPlan $seatPlan) => [
                $seatPlan->id,
                $seatPlan->name,
                $seatPlan->event?->name ?? '-',
                count($seatPlan->data['blocks'] ?? []),
            ]),
        );

        return self::SUCCESS;
    }
}
