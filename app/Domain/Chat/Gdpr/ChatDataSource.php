<?php

namespace App\Domain\Chat\Gdpr;

use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-018
 */
class ChatDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'chat';
    }

    public function label(): string
    {
        return 'Authored chat messages (including soft-deleted) and room memberships';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        $messages = ChatMessage::withTrashed()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->with('room:id,key,title')
            ->get()
            ->map(function (ChatMessage $message) use ($user, $context): array {
                $row = $message->attributesToArray();
                $row['room_key'] = $message->room->key ?? null;
                $row['room_title'] = $message->room->title ?? null;
                // Mentions are user ids — obfuscate any that are not the subject.
                $row['mentions_json'] = collect($message->mentions_json ?? [])
                    ->map(fn (int $id) => $id === $user->id
                        ? 'subject'
                        : $context->obfuscateUser($id, 'mentioned'))
                    ->all();

                if ($message->deleted_by !== null) {
                    $row['deleted_by'] = $message->deleted_by === $user->id
                        ? 'subject'
                        : $context->obfuscateUser($message->deleted_by, 'moderator');
                }

                return $row;
            })
            ->all();

        $memberships = ChatRoomMembership::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->with('room:id,key,title')
            ->get()
            ->map(function (ChatRoomMembership $m): array {
                $row = $m->attributesToArray();
                $row['room_key'] = $m->room->key ?? null;
                $row['room_title'] = $m->room->title ?? null;

                return $row;
            })
            ->all();

        return new GdprDataSourceResult([
            'authored_messages' => $messages,
            'room_memberships' => $memberships,
        ]);
    }
}
