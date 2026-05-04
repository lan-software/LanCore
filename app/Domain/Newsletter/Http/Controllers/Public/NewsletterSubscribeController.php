<?php

namespace App\Domain\Newsletter\Http\Controllers\Public;

use App\Domain\Newsletter\Actions\SubscribeAnonymous;
use App\Domain\Newsletter\Actions\SubscribeUserToList;
use App\Domain\Newsletter\Exceptions\ListmonkException;
use App\Domain\Newsletter\Http\Requests\Public\NewsletterSubscribeRequest;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Public-facing endpoint hit by the `/countdown` page newsletter form.
 * Anonymous emails are accepted; authenticated users get their submission
 * mirrored into `newsletter_list_user`.
 *
 * @see docs/mil-std-498/SSS.md CAP-CTD-002
 * @see docs/mil-std-498/SRS.md NLT-F-006, CTD-F-002
 */
class NewsletterSubscribeController extends Controller
{
    public function store(
        NewsletterSubscribeRequest $request,
        SubscribeAnonymous $subscribeAnonymous,
        SubscribeUserToList $subscribeUser,
    ): RedirectResponse {
        $list = NewsletterList::query()
            ->where('is_default_public', true)
            ->first();

        if ($list === null) {
            return back()->withErrors([
                'email' => __('Newsletter signup is not currently available.'),
            ])->withInput();
        }

        $email = (string) $request->validated('email');
        $name = $request->validated('name');
        $user = $request->user();

        try {
            if ($user !== null && strcasecmp((string) $user->email, $email) === 0) {
                $subscribeUser->execute($user, $list);
            } else {
                $subscribeAnonymous->execute($email, $name, $list);
            }
        } catch (ListmonkException $e) {
            Log::warning('Newsletter: public signup failed', [
                'kind' => $e->kind,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => __('We could not complete your signup right now. Please try again later.'),
            ])->withInput();
        }

        return back()->with('status', __('Thanks! Please check your inbox to confirm your subscription.'));
    }
}
