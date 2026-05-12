<?php

use App\Domain\Chat\Enums\ModerationAction;
use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\ChatModerationAction;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('creates a chat room and round-trips status as an enum', function (): void {
    $room = ChatRoom::create([
        'key' => 'competition:1:match:42',
        'consumer_domain' => 'Competition',
        'policy_class' => 'App\\Domain\\Competition\\Chat\\MatchRoomPolicy',
        'status' => RoomStatus::Open,
        'opened_at' => now(),
    ]);

    expect($room->fresh()->status)->toBe(RoomStatus::Open);
});

it('enforces unique (room_id, user_id) on memberships', function (): void {
    $user = User::factory()->create();
    $room = ChatRoom::create([
        'key' => 'competition:1:match:43',
        'consumer_domain' => 'Competition',
        'policy_class' => 'X',
        'status' => RoomStatus::Open,
    ]);
    ChatRoomMembership::create([
        'room_id' => $room->id,
        'user_id' => $user->id,
        'joined_at' => now(),
    ]);

    expect(function () use ($room, $user): void {
        ChatRoomMembership::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'joined_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

it('soft-deletes a chat message and preserves the body', function (): void {
    $user = User::factory()->create();
    $room = ChatRoom::create([
        'key' => 'competition:1:match:44',
        'consumer_domain' => 'Competition',
        'policy_class' => 'X',
        'status' => RoomStatus::Open,
    ]);
    $msg = ChatMessage::create([
        'room_id' => $room->id,
        'user_id' => $user->id,
        'body' => 'gg wp',
    ]);

    $msg->delete();

    $fresh = ChatMessage::withTrashed()->find($msg->id);
    expect($fresh->trashed())->toBeTrue();
    expect($fresh->body)->toBe('gg wp');
});

it('persists moderation action as an enum cast', function (): void {
    $actor = User::factory()->create();
    $room = ChatRoom::create([
        'key' => 'competition:1:match:45',
        'consumer_domain' => 'Competition',
        'policy_class' => 'X',
        'status' => RoomStatus::Open,
    ]);

    $action = ChatModerationAction::create([
        'room_id' => $room->id,
        'actor_id' => $actor->id,
        'action' => ModerationAction::CloseRoom,
        'reason' => 'spam',
    ]);

    expect($action->fresh()->action)->toBe(ModerationAction::CloseRoom);
});

it('has the indexes documented in DBDD §4.20', function (): void {
    $roomIndexes = collect(Schema::getIndexes('chat_rooms'))
        ->pluck('name')
        ->all();

    $messageIndexes = collect(Schema::getIndexes('chat_messages'))
        ->pluck('name')
        ->all();

    expect($roomIndexes)->toContain('chat_rooms_key_unique');
    expect($roomIndexes)->toContain('chat_rooms_consumer_domain_status_index');
    expect($messageIndexes)->toContain('chat_messages_room_id_created_at_desc_idx');
});
