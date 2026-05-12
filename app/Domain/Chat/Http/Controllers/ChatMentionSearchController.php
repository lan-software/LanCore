<?php

namespace App\Domain\Chat\Http\Controllers;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\PolicyResolver;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-018
 * @see docs/mil-std-498/IDD.md §3.14
 */
class ChatMentionSearchController extends Controller
{
    public function __construct(
        private readonly PolicyResolver $policyResolver,
    ) {}

    public function __invoke(Request $request, ChatRoom $room): JsonResponse
    {
        $user = $request->user();
        $policy = $this->policyResolver->resolve($room);

        if (! $policy->canView($user, $room)) {
            throw new AuthorizationException;
        }

        $q = trim((string) $request->query('q', ''));

        if (strlen($q) < 1) {
            return response()->json(['users' => []]);
        }

        // Restrict candidates to current room members so non-viewable users are never surfaced.
        $candidates = $room->memberships()
            ->with('user:id,name,username')
            ->get()
            ->pluck('user')
            ->filter()
            ->filter(function (User $candidate) use ($q): bool {
                $needle = mb_strtolower($q);
                $username = mb_strtolower((string) $candidate->username);
                $name = mb_strtolower((string) $candidate->name);

                return str_contains($username, $needle) || str_contains($name, $needle);
            })
            ->take(8)
            ->map(fn (User $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'username' => $candidate->username,
            ])
            ->values()
            ->all();

        return response()->json(['users' => $candidates]);
    }
}
