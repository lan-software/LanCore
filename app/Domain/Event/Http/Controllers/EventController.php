<?php

namespace App\Domain\Event\Http\Controllers;

use App\Domain\Event\Actions\CreateEvent;
use App\Domain\Event\Actions\DeleteEvent;
use App\Domain\Event\Actions\PublishEvent;
use App\Domain\Event\Actions\UnpublishEvent;
use App\Domain\Event\Actions\UpdateEvent;
use App\Domain\Event\Http\Requests\EventIndexRequest;
use App\Domain\Event\Http\Requests\StoreEventRequest;
use App\Domain\Event\Http\Requests\UpdateEventRequest;
use App\Domain\Event\Http\Requests\UpdateEventThemeRequest;
use App\Domain\Event\Models\Event;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Domain\Theme\Models\Theme;
use App\Domain\Venue\Models\Venue;
use App\Http\Controllers\Controller;
use App\Support\StorageRole;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-EVT-001, CAP-EVT-002
 * @see docs/mil-std-498/SRS.md EVT-F-001, EVT-F-003, EVT-F-004
 */
class EventController extends Controller
{
    public function __construct(
        private readonly CreateEvent $createEvent,
        private readonly UpdateEvent $updateEvent,
        private readonly DeleteEvent $deleteEvent,
        private readonly PublishEvent $publishEvent,
        private readonly UnpublishEvent $unpublishEvent,
    ) {}

    public function index(EventIndexRequest $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $query = Event::with('venue');

        if ($search = $request->validated('search')) {
            $query->whereLike('name', "%{$search}%");
        }

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        $sortColumn = $request->validated('sort') ?? 'start_date';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $events = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('events/Index', [
            'events' => $events,
            'filters' => $request->only(['search', 'sort', 'direction', 'status', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Event::class);

        return Inertia::render('events/Create', [
            'venues' => Venue::dropdownOptions(),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $data = $request->safe()->except(['banner_images']);
        $bannerImages = [];
        if ($request->hasFile('banner_images')) {
            foreach ($request->file('banner_images') as $file) {
                $stored = $file->store('events/banners', StorageRole::publicDiskName());
                if ($stored !== false && $stored !== '') {
                    $bannerImages[] = $stored;
                }
            }
        }
        $data['banner_images'] = $bannerImages;

        $this->createEvent->execute($data);

        return redirect()->route('events.index');
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        $eventData = $event->load('venue')->toArray();
        $bannerImages = array_values(array_filter($event->banner_images ?? [], fn ($p) => is_string($p) && $p !== ''));
        $eventData['banner_images'] = $bannerImages;
        $eventData['banner_image_urls'] = array_map(
            fn (string $path) => StorageRole::publicUrl($path),
            $bannerImages,
        );

        return Inertia::render('events/Edit', [
            'event' => $eventData,
            'venues' => Venue::dropdownOptions(),
            'orgaTeams' => OrgaTeam::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'themes' => Theme::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * @see docs/mil-std-498/SSS.md CAP-EVT-008, CAP-THM-002
     * @see docs/mil-std-498/SRS.md THM-F-004
     */
    public function updateTheme(UpdateEventThemeRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->update([
            'theme_id' => $request->validated('theme_id'),
        ]);

        return back();
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $data = $request->safe()->except(['banner_images', 'banner_images_to_remove']);

        $currentImages = $event->banner_images ?? [];

        // Remove only images that actually belong to this event.
        $imagesToRemove = array_filter(
            $request->input('banner_images_to_remove', []),
            fn (string $path) => in_array($path, $currentImages, true),
        );
        if (! empty($imagesToRemove)) {
            StorageRole::public()->delete(array_values($imagesToRemove));
            $currentImages = array_values(array_diff($currentImages, $imagesToRemove));
        }

        // Append newly uploaded images.
        if ($request->hasFile('banner_images')) {
            foreach ($request->file('banner_images') as $file) {
                $stored = $file->store('events/banners', StorageRole::publicDiskName());
                if ($stored !== false && $stored !== '') {
                    $currentImages[] = $stored;
                }
            }
        }

        $data['banner_images'] = $currentImages;

        $this->updateEvent->execute($event, $data);

        return back();
    }

    public function publish(Event $event): RedirectResponse
    {
        $this->authorize('publish', $event);

        $this->publishEvent->execute($event);

        return back();
    }

    public function unpublish(Event $event): RedirectResponse
    {
        $this->authorize('publish', $event);

        $this->unpublishEvent->execute($event);

        return back();
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $this->deleteEvent->execute($event);

        return redirect()->route('events.index');
    }
}
