<?php

namespace App\Console\Commands\Programs;

use App\Domain\Program\Enums\ProgramVisibility;
use App\Domain\Program\Models\Program;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('programs:list {--visibility= : Filter by visibility (public, internal, private)} {--event= : Filter by event ID}')]
#[Description('List all programs')]
class ListPrograms extends Command
{
    public function handle(): int
    {
        $query = Program::query()->with('event')->withCount('timeSlots');

        if ($this->option('visibility') !== null) {
            $visibility = ProgramVisibility::tryFrom($this->option('visibility'));

            if (! $visibility) {
                $this->error("Invalid visibility '{$this->option('visibility')}'. Valid values: ".implode(', ', array_column(ProgramVisibility::cases(), 'value')));

                return self::FAILURE;
            }

            $query->where('visibility', $visibility);
        }

        if ($this->option('event') !== null) {
            $query->where('event_id', $this->option('event'));
        }

        $programs = $query->orderBy('sort_order')->get();

        if ($programs->isEmpty()) {
            $this->info('No programs found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Visibility', 'Event', 'Time Slots', 'Sort'],
            $programs->map(fn (Program $program) => [
                $program->id,
                $program->name,
                $program->visibility->value,
                $program->event?->name ?? '-',
                $program->time_slots_count,
                $program->sort_order,
            ]),
        );

        return self::SUCCESS;
    }
}
