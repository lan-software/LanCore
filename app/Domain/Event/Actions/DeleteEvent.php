<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteEvent
{
    public function execute(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            if ($event->banner_image) {
                Storage::delete($event->banner_image);
            }
            $event->delete();
        });
    }
}
