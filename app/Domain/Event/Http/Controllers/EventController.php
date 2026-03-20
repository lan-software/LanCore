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
use App\Domain\Event\Models\Event;
use App\Domain\Venue\Models\Venue;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

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
            $query->where('name', 'ilike', "%{$search}%");
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
            'venues' => Venue::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $data = $request->validated();
        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('events/banners');
        }

        $this->createEvent->execute($data);

        return redirect()->route('events.index');
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        $eventData = $event->load('venue')->toArray();
        $eventData['banner_image_url'] = $event->banner_image ? Storage::url($event->banner_image) : null;

        return Inertia::render('events/Edit', [
            'event' => $eventData,
            'venues' => Venue::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $data = $request->safe()->except(['banner_image', 'remove_banner_image']);

        if ($request->hasFile('banner_image')) {
            if ($event->banner_image) {
                Storage::delete($event->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('events/banners');
        } elseif ($request->boolean('remove_banner_image')) {
            if ($event->banner_image) {
                Storage::delete($event->banner_image);
            }
            $data['banner_image'] = null;
        }

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
