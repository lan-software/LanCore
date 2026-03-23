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
            $bannerImages = $event->banner_images ?? [];
            if (! empty($bannerImages)) {
                Storage::delete($bannerImages);
            }
            $event->delete();
        });
    }
}
