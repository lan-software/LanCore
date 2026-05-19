<?php

namespace App\Domain\Newsletter\Http\Controllers\Admin;

use App\Domain\Newsletter\Actions\SubscribeUserToList;
use App\Domain\Newsletter\Actions\UnsubscribeUserFromList;
use App\Domain\Newsletter\Enums\SubscriptionStatus;
use App\Domain\Newsletter\Http\Requests\Admin\UpdateUserSubscriptionsRequest;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Admin endpoint for managing a single user's newsletter subscriptions
 * from the user detail page (`/users/{user}` → Newsletter tab). Mirrors
 * the diff-based update path used by the per-user `EmailSettingsController`,
 * but acts on behalf of any user the admin can manage.
 *
 * @see docs/mil-std-498/SSS.md CAP-NLT-002
 * @see docs/mil-std-498/SRS.md NLT-F-003
 */
class UserSubscriptionsController extends Controller
{
    public function update(
        UpdateUserSubscriptionsRequest $request,
        User $user,
        SubscribeUserToList $subscribe,
        UnsubscribeUserFromList $unsubscribe,
    ): RedirectResponse {
        $this->authorize('update', $user);

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
