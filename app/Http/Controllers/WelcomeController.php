<?php

namespace App\Http\Controllers;

use App\Domain\Announcement\Models\Announcement;
use App\Domain\Event\Models\Event;
use App\Domain\News\Models\NewsArticle;
use App\Domain\Program\Enums\ProgramVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class WelcomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $nextEvent = Event::published()
            ->upcoming()
            ->with([
                'venue.address',
                'venue.images',
                'programs' => fn ($q) => $q->where('visibility', ProgramVisibility::Public)->orderBy('sort_order'),
                'programs.sponsors',
                'programs.timeSlots' => fn ($q) => $q->where('visibility', ProgramVisibility::Public)->orderBy('starts_at'),
                'programs.timeSlots.sponsors',
                'sponsors.sponsorLevel',
                'seatPlans',
            ])
            ->orderBy('start_date')
            ->first();

        $nextEventData = null;

        if ($nextEvent) {
            $nextEventData = $nextEvent->toArray();
            $nextEventData['banner_image_url'] = $nextEvent->banner_image ? Storage::fileUrl($nextEvent->banner_image) : null;

            if (isset($nextEventData['venue']['images'])) {
                $nextEventData['venue']['images'] = collect($nextEventData['venue']['images'])->map(function (array $image) {
                    $image['url'] = Storage::fileUrl($image['path']);

                    return $image;
                })->all();
            }

            if (isset($nextEventData['sponsors'])) {
                $nextEventData['sponsors'] = collect($nextEventData['sponsors'])->map(function (array $sponsor) {
                    $sponsor['logo_url'] = $sponsor['logo'] ? Storage::fileUrl($sponsor['logo']) : null;

                    return $sponsor;
                })->all();
            }
        }

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'nextEvent' => $nextEventData,
            'latestNews' => $this->getLatestNews(),
            'announcements' => $nextEvent ? $this->getActiveAnnouncements($nextEvent, $request) : [],
            'dismissedAnnouncementIds' => $nextEvent ? $this->getDismissedAnnouncementIds($nextEvent, $request) : [],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLatestNews(): array
    {
        $articles = NewsArticle::published()
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return $articles->map(function (NewsArticle $article) {
            $data = $article->toArray();
            $data['image_url'] = $article->image ? Storage::fileUrl($article->image) : null;

            return $data;
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getActiveAnnouncements(Event $event, Request $request): array
    {
        $query = Announcement::query()
            ->where('event_id', $event->id)
            ->published()
            ->with('author:id,name')
            ->orderByDesc('published_at');

        if ($request->user()) {
            $query->notDismissedBy($request->user());
        }

        return $query->get()->toArray();
    }

    /**
     * @return array<int, int>
     */
    private function getDismissedAnnouncementIds(Event $event, Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        return $request->user()
            ->dismissedAnnouncements()
            ->where('event_id', $event->id)
            ->pluck('announcements.id')
            ->all();
    }
}
