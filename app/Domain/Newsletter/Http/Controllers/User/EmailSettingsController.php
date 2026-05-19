<?php

namespace App\Domain\Newsletter\Http\Controllers\User;

use App\Domain\Newsletter\Actions\SubscribeUserToList;
use App\Domain\Newsletter\Actions\UnsubscribeUserFromList;
use App\Domain\Newsletter\Enums\SubscriptionStatus;
use App\Domain\Newsletter\Http\Requests\User\UpdateEmailSettingsRequest;
use App\Domain\Newsletter\Jobs\RefreshUserSubscriptionsJob;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-NLT-002
 * @see docs/mil-std-498/SRS.md NLT-F-003, NLT-F-004
 */
class EmailSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        if ($user !== null && config('listmonk.enabled')) {
            RefreshUserSubscriptionsJob::dispatch($user->id)->afterResponse();
        }

        $lists = NewsletterList::query()
            ->where('is_user_selectable', true)
            ->orderBy('name')
            ->get();

        $payload = $lists->map(function (NewsletterList $list) use ($user): array {
            $pivot = $user === null
                ? null
                : $list->subscribers()->where('users.id', $user->id)->first()?->pivot;

            return [
                'id' => $list->id,
                'name' => $list->name,
                'description' => $list->description,
                'tags' => $list->tags ?? [],
                'status' => $pivot?->status?->value ?? SubscriptionStatus::Unsubscribed->value,
                'subscribed_at' => $pivot?->subscribed_at,
                'last_synced_at' => $pivot?->last_synced_at,
            ];
        })->all();

        return Inertia::render('settings/EmailSettings', [
            'lists' => $payload,
            'listmonkEnabled' => (bool) config('listmonk.enabled'),
        ]);
    }

    public function update(
        UpdateEmailSettingsRequest $request,
        SubscribeUserToList $subscribe,
        UnsubscribeUserFromList $unsubscribe,
    ): RedirectResponse {
        $user = $request->user();
        $desiredIds = collect($request->validated('subscribed_list_ids', []))
            ->map(fn ($id): string => (string) $id)
            ->unique()
            ->all();

        $availableLists = NewsletterList::query()
            ->where('is_user_selectable', true)
            ->get();

        foreach ($availableLists as $list) {
            $shouldSubscribe = in_array($list->id, $desiredIds, true);
            $pivot = $list->subscribers()->where('users.id', $user->id)->first()?->pivot;
            $isSubscribed = $pivot !== null && $pivot->status === SubscriptionStatus::Enabled;

            if ($shouldSubscribe && ! $isSubscribed) {
                $subscribe->execute($user, $list);
            } elseif (! $shouldSubscribe && $isSubscribed) {
                $unsubscribe->execute($user, $list);
            }
        }

        return back();
    }
}
