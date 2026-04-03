<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Competition\Actions\HandleLanBracketsWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanBracketsWebhookController extends Controller
{
    public function __invoke(Request $request, HandleLanBracketsWebhook $handler): JsonResponse
    {
        $signature = $request->header('X-LanBrackets-Signature', '');
        $event = $request->header('X-LanBrackets-Event', '');
        $body = $request->getContent();

        $expectedSignature = hash_hmac('sha256', $body, config('lanbrackets.webhook_secret', ''));

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $handler->execute($event, $request->json()->all());

        return response()->json(['message' => 'OK']);
    }
}
