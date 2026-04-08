<?php

namespace App\Domain\Announcement\Services;

use App\Domain\Announcement\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ActiveAnnouncementsProvider
{
    /**
     * @return list<array{id: int, audience: string|null, severity: string|null, title: string, body: string|null, starts_at: string|null, ends_at: null, dismissible: bool}>
     */
    public function forCurrentUser(?User $user): array
    {
        $cacheKey = 'announcement.active.'.($user?->id ?? 'guest');

        return Cache::remember($cacheKey, 60, function () use ($user): array {
            $query = Announcement::query()
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereIn('audience', ['lancore_only', 'all'])
                ->orderByDesc('published_at');

            if ($user !== null) {
                $query->whereDoesntHave(
                    'dismissedByUsers',
                    fn ($q) => $q->where('users.id', $user->id),
                );
            }

            return $query->get()
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
    }
}
