<?php

namespace App\Domain\Announcement\Http\Controllers;

use App\Domain\Announcement\Events\AnnouncementsViewed;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Event\Models\Event;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicAnnouncementController extends Controller
{
    public function __invoke(Request $request, Event $event): Response
    {
        $announcements = Announcement::query()
            ->where('event_id', $event->id)
            ->published()
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->get();

        $dismissedIds = [];
        if ($request->user()) {
            AnnouncementsViewed::dispatch($request->user());

            $dismissedIds = $request->user()
                ->dismissedAnnouncements()
                ->pluck('announcements.id')
                ->all();
        }

        return Inertia::render('announcements/Public', [
            'event' => $event->only('id', 'name'),
            'announcements' => $announcements,
            'dismissedIds' => $dismissedIds,
        ]);
    }
}
