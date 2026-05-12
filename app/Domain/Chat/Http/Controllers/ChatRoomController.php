<?php

namespace App\Domain\Chat\Http\Controllers;

use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Chat\Services\PolicyResolver;
use App\Domain\Competition\Chat\CompetitionMemberAnnotator;
use App\Domain\Presence\Services\PresenceTracker;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-026
 */
class ChatRoomController extends Controller
{
    public function __construct(
        private readonly PolicyResolver $policyResolver,
        private readonly PresenceTracker $presenceTracker,
        private readonly CompetitionMemberAnnotator $competitionMemberAnnotator,
    ) {}

    public function show(ChatRoom $room): Response
    {
        $user = request()->user();
        $policy = $this->policyResolver->resolve($room);

        if (! $policy->canView($user, $room)) {
            throw new AuthorizationException;
        }

        $messages = $room->messages()
            ->withTrashed()
            ->with('user:id,name,username')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($message) => $this->serializeMessage($message))
            ->all();

        $members = $this->competitionMemberAnnotator->annotate(
            $room->key,
            $room->memberships()
                ->with('user:id,name,username')
                ->get()
                ->map(fn ($membership) => [
                    'user_id' => $membership->user_id,
                    'name' => $membership->user?->name,
                    'username' => $membership->user?->username,
                    'role' => $membership->role,
                    'muted_until' => $membership->muted_until?->toIso8601String(),
                ])
                ->all(),
        );

        $memberIds = collect($members)->pluck('user_id')->all();
        $presence = [];
        foreach ($this->presenceTracker->bulkStatusFor($memberIds) as $userId => $status) {
            $presence[$userId] = $status->value;
        }

        return Inertia::render('chat/Room', [
            'room' => [
                'id' => $room->id,
                'key' => $room->key,
                'title' => $room->title,
                'status' => $room->status->value,
                'can_post' => $policy->canPost($user, $room),
                'can_moderate' => $policy->canModerate($user, $room),
                'is_open' => $room->status === RoomStatus::Open,
                'is_archived' => $room->status === RoomStatus::Archived,
                'is_write_locked' => $room->status === RoomStatus::WriteLocked,
            ],
            'messages' => $messages,
            'members' => $members,
            'memberPresence' => $presence,
        ]);
    }

    /**
     * Insert a transient "observer" membership for the requesting user. Idempotent —
     * if a membership already exists (e.g. a participant returning), no row is added.
     * Used by admin pages that embed the chat but where the admin is not a team
     * member. Pair with `leaveObserver()` on page unload.
     */
    public function joinObserver(Request $request, ChatRoom $room): JsonResponse
    {
        $user = $request->user();
        $policy = $this->policyResolver->resolve($room);

        if (! $policy->canView($user, $room)) {
            throw new AuthorizationException;
        }

        ChatRoomMembership::firstOrCreate(
            ['room_id' => $room->id, 'user_id' => $user->id],
            ['role' => 'observer', 'joined_at' => now()],
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Remove a transient observer membership inserted by `joinObserver`. Only
     * removes rows where `role = 'observer'` so participant memberships are not
     * deleted by mistake.
     */
    public function leaveObserver(Request $request, ChatRoom $room): JsonResponse
    {
        $user = $request->user();

        ChatRoomMembership::query()
            ->where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('role', 'observer')
            ->delete();

        return response()->json(['ok' => true]);
    }

    private function serializeMessage($message): array
    {
        return [
            'id' => $message->id,
            'room_id' => $message->room_id,
            'user_id' => $message->user_id,
            'user' => [
                'id' => $message->user?->id,
                'name' => $message->user?->name,
                'username' => $message->user?->username,
            ],
            'body' => $message->body,
            'mentions' => $message->mentions_json ?? [],
            'deleted_at' => $message->deleted_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
