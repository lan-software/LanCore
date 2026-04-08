<?php

namespace App\Domain\Announcement\Http\Controllers;

use App\Domain\Announcement\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicAnnouncementFeedController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $payload = Cache::remember('announcement.feed', 30, function (): array {
            return Announcement::query()
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereIn('audience', ['satellites', 'all'])
                ->orderByDesc('published_at')
                ->get()
                ->map(fn (Announcement $a): array => [
                    'id' => $a->id,
                    'audience' => $a->audience?->value,
                    'severity' => $a->priority?->value,
                    'title' => $a->title,
                    'body' => $a->description,
                    'starts_at' => $a->published_at?->toIso8601String(),
                    'ends_at' => null,
                    'dismissible' => true,
                ])
                ->values()
                ->all();
        });

        return response()->json(['data' => $payload]);
    }
}
