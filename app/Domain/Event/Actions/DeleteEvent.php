<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;

class DeleteEvent
{
    public function execute(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $event->delete();
        });
    }
}
