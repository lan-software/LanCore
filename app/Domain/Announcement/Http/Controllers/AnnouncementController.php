<?php

namespace App\Domain\Announcement\Http\Controllers;

use App\Domain\Announcement\Actions\CreateAnnouncement;
use App\Domain\Announcement\Actions\DeleteAnnouncement;
use App\Domain\Announcement\Actions\UpdateAnnouncement;
use App\Domain\Announcement\Http\Requests\StoreAnnouncementRequest;
use App\Domain\Announcement\Http\Requests\UpdateAnnouncementRequest;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Event\Models\Event;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly CreateAnnouncement $createAnnouncement,
        private readonly UpdateAnnouncement $updateAnnouncement,
        private readonly DeleteAnnouncement $deleteAnnouncement,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Announcement::class);

        $query = Announcement::with(['author:id,name', 'event:id,name']);

        if ($search = $request->input('search')) {
            $query->where('title', 'ilike', "%{$search}%");
        }

        $sortColumn = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortColumn, $sortDirection);

        $announcements = $query->paginate($request->input('per_page', 20))->withQueryString();

        return Inertia::render('announcements/Index', [
            'announcements' => $announcements,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Announcement::class);

        return Inertia::render('announcements/Create', [
            'events' => Event::query()->orderByDesc('start_date')->get(['id', 'name']),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        $attributes = $request->safe()->except(['publish_now']);
        $attributes['author_id'] = $request->user()->id;

        if ($request->boolean('publish_now')) {
            $attributes['published_at'] = now();
        }

        $this->createAnnouncement->execute($attributes);

        return redirect()->route('announcements.index');
    }

    public function edit(Announcement $announcement): Response
    {
        $this->authorize('update', $announcement);

        $announcement->load(['author:id,name', 'event:id,name']);

        return Inertia::render('announcements/Edit', [
            'announcement' => $announcement,
            'events' => Event::query()->orderByDesc('start_date')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $attributes = $request->safe()->except(['publish_now']);

        if ($request->boolean('publish_now') && $announcement->published_at === null) {
            $attributes['published_at'] = now();
        }

        $this->updateAnnouncement->execute($announcement, $attributes);

        return back();
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        $this->deleteAnnouncement->execute($announcement);

        return redirect()->route('announcements.index');
    }
}
