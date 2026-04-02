<?php

namespace App\Domain\Program\Actions;

use App\Domain\Event\Models\Event;
use App\Domain\Program\Models\Program;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md PRG-F-001
 */
class DeleteProgram
{
    public function execute(Program $program): void
    {
        DB::transaction(function () use ($program): void {
            Event::where('primary_program_id', $program->id)
                ->update(['primary_program_id' => null]);

            $program->delete();
        });
    }
}
