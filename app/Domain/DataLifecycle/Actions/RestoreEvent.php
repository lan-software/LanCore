<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;

class RestoreEvent
{
    public function execute(Event $event): void
    {
        DB::transaction(fn () => $event->restore());
    }
}
