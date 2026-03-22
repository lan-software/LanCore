<?php

namespace App\Domain\Notification\Http\Controllers;

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
            ],
        );

        return Inertia::render('settings/Notifications', [
            'preferences' => $preferences,
        ]);
    }

    public function update(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $preferences = NotificationPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
        );

        $preferences->update($request->validated());

        return back();
    }
}
