<?php

namespace App\Domain\Notification\Http\Controllers;

use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Program\Models\Program;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramSubscriptionController extends Controller
{
    public function toggle(Request $request, Program $program): JsonResponse
    {
        $userId = $request->user()->id;

        $subscription = ProgramNotificationSubscription::query()
            ->where('user_id', $userId)
            ->where('program_id', $program->id)
            ->first();

        if ($subscription) {
            $subscription->delete();

            return response()->json(['subscribed' => false]);
        }

        ProgramNotificationSubscription::create([
            'user_id' => $userId,
            'program_id' => $program->id,
        ]);

        return response()->json(['subscribed' => true]);
    }
}
