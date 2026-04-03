<?php

namespace App\Domain\Notification\Http\Controllers;

use App\Domain\Notification\Http\Requests\StorePushSubscriptionRequest;
use App\Domain\Notification\Models\PushSubscription;
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
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();

        PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
            ],
            [
                'public_key' => $data['public_key'],
                'auth_token' => $data['auth_token'],
                'content_encoding' => $data['content_encoding'] ?? 'aesgcm',
            ]
        );

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
