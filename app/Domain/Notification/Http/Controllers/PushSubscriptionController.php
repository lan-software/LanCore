<?php

namespace App\Domain\Notification\Http\Controllers;

use App\Domain\Notification\Actions\StorePushSubscription;
use App\Domain\Notification\Http\Requests\StorePushSubscriptionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @see docs/mil-std-498/SSS.md CAP-NTF-002
 * @see docs/mil-std-498/SRS.md NTF-F-003
 * @see docs/mil-std-498/SRS.md NTF-F-007
 */
class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request, StorePushSubscription $action): JsonResponse
    {
        $action->execute($request->user(), $request->validated());

        return response()->json(['subscribed' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $endpoint = $request->input('endpoint');

        $request->user()
            ->pushSubscriptions()
            ->where('endpoint', $endpoint)
            ->delete();

        return response()->json(['subscribed' => false]);
    }

    public function dismiss(Request $request): JsonResponse
    {
        $request->session()->put('push_prompt_dismissed', true);

        return response()->json(['dismissed' => true]);
    }
}
