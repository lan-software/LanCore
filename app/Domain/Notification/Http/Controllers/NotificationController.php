<?php

namespace App\Domain\Notification\Http\Controllers;

use App\Domain\Notification\Events\NotificationsArchived;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->whereNull('archived_at')
            ->paginate(20);

        return Inertia::render('notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function archivedIndex(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->whereNotNull('archived_at')
            ->paginate(20);

        return Inertia::render('notifications/Archive', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public function archive(Request $request, string $id): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->findOrFail($id)
            ->update(['archived_at' => now()]);

        return back();
    }

    public function archiveAll(Request $request): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);

        NotificationsArchived::dispatch($request->user());

        return back();
    }
}
