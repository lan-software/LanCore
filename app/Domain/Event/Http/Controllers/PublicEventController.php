<?php

namespace App\Domain\Event\Http\Controllers;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Program\Enums\ProgramVisibility;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @see docs/mil-std-498/SRS.md EVT-F-009
 */
class PublicEventController extends Controller
{
    public function index(): Response
    {
        return $this->render(false);
    }

    public function past(): Response
    {
        return $this->render(true);
    }

    private function render(bool $isPast): Response
    {

        $query = Event::published()
            ->with('venue');

        if ($isPast) {
            $query->past()->orderByDesc('start_date');
        } else {
            $query->upcoming()->orderBy('start_date');
        }

        $events = $query->paginate(12)->withQueryString();

        $events->through(function (Event $event) {
            $eventData = $event->toArray();
            $bannerImages = array_values(array_filter($event->banner_images ?? [], fn ($p) => is_string($p) && $p !== ''));
            $eventData['banner_images'] = $bannerImages;
            $eventData['banner_image_urls'] = array_map(
                fn (string $path) => Storage::fileUrl($path),
                $bannerImages,
            );

            return $eventData;
        });

        return Inertia::render('events/Public', [
            'events' => $events,
            'mode' => $isPast ? 'past' : 'upcoming',
        ]);
    }

    public function show(Event $event): Response
    {
        if ($event->status !== EventStatus::Published) {
            throw new NotFoundHttpException;
        }

        $event->load([
            'venue.address',
            'venue.images',
            'programs' => fn ($q) => $q->where('visibility', ProgramVisibility::Public)->orderBy('sort_order'),
            'programs.sponsors',
            'programs.timeSlots' => fn ($q) => $q->where('visibility', ProgramVisibility::Public)->orderBy('starts_at'),
            'programs.timeSlots.sponsors',
            'sponsors.sponsorLevel',
            'seatPlans',
        ]);

        $eventData = $event->toArray();
        $bannerImages = array_values(array_filter($event->banner_images ?? [], fn ($p) => is_string($p) && $p !== ''));
        $eventData['banner_images'] = $bannerImages;
        $eventData['banner_image_urls'] = array_map(
            fn (string $path) => Storage::fileUrl($path),
            $bannerImages,
        );

        if (isset($eventData['venue']['images'])) {
            $eventData['venue']['images'] = collect($eventData['venue']['images'])->map(function (array $image) {
                $image['url'] = Storage::url($image['path']);

                return $image;
            })->all();
        }

        if (isset($eventData['sponsors'])) {
            $eventData['sponsors'] = collect($eventData['sponsors'])->map(function (array $sponsor) {
                $sponsor['logo_url'] = $sponsor['logo'] ? Storage::url($sponsor['logo']) : null;

                return $sponsor;
            })->all();
        }

        $isPast = $event->end_date !== null && $event->end_date->isPast();

        if ($isPast) {
            $label = 'Past Event';
        } else {
            $nextUpcomingId = Event::published()->upcoming()->orderBy('start_date')->value('id');
            $label = $nextUpcomingId === $event->id ? 'Next Event' : 'Upcoming Event';
        }

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'nextEvent' => $eventData,
            'eventLabel' => $label,
            'latestNews' => [],
            'announcements' => [],
            'dismissedAnnouncementIds' => [],
            'openCompetitions' => [],
        ]);
    }
}
