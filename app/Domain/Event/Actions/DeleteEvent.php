<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Models\Event;
use App\Support\StorageRole;
use Illuminate\Support\Facades\DB;

class DeleteEvent
{
    public function execute(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $bannerImages = $event->banner_images ?? [];
            if (! empty($bannerImages)) {
                StorageRole::public()->delete($bannerImages);
            }
            $event->delete();
        });
    }
}
