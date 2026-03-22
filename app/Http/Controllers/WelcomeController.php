<?php

namespace App\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\News\Models\NewsArticle;
use App\Domain\Program\Enums\ProgramVisibility;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class WelcomeController extends Controller
{
    public function __invoke(): Response
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
}
