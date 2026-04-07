<?php

namespace App\Domain\Notification\Http\Controllers;

use App\Domain\Notification\Actions\ToggleProgramSubscription;
use App\Domain\Program\Models\Program;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @see docs/mil-std-498/SRS.md NTF-F-004
 */
class ProgramSubscriptionController extends Controller
{
    public function toggle(Request $request, Program $program, ToggleProgramSubscription $action): JsonResponse
    {
        $subscribed = $action->execute($request->user(), $program);

        return response()->json(['subscribed' => $subscribed]);
    }
}
