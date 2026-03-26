<?php

namespace App\Domain\Notification\Http\Controllers;

use App\Domain\Notification\Events\NotificationPreferencesUpdated;
use App\Domain\Notification\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Domain\Notification\Models\NotificationPreference;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $preferences = NotificationPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'mail_on_news' => true,
                'mail_on_events' => true,
                'mail_on_news_comments' => true,
                'mail_on_program_time_slots' => true,
                'mail_on_announcements' => true,
                'push_on_news' => false,
                'push_on_events' => false,
                'push_on_news_comments' => false,
                'push_on_program_time_slots' => false,
                'push_on_announcements' => false,
            ],
        );

        return Inertia::render('settings/Notifications', [
            'preferences' => [
                'mail_on_news' => $preferences->mail_on_news,
                'mail_on_events' => $preferences->mail_on_events,
                'mail_on_news_comments' => $preferences->mail_on_news_comments,
                'mail_on_program_time_slots' => $preferences->mail_on_program_time_slots,
                'mail_on_announcements' => $preferences->mail_on_announcements,
                'push_on_news' => $preferences->push_on_news,
                'push_on_events' => $preferences->push_on_events,
                'push_on_news_comments' => $preferences->push_on_news_comments,
                'push_on_program_time_slots' => $preferences->push_on_program_time_slots,
                'push_on_announcements' => $preferences->push_on_announcements,
            ],
        ]);
    }

    public function update(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $preferences = NotificationPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
        );

        $preferences->update($request->validated());

        NotificationPreferencesUpdated::dispatch($request->user());

        return back();
    }
}
