<?php

namespace App\Domain\Chat\Http\Controllers;

use App\Domain\Chat\Actions\DeleteMessage;
use App\Domain\Chat\Actions\PostMessage;
use App\Domain\Chat\Exceptions\DuplicateMessageException;
use App\Domain\Chat\Exceptions\PostUnauthorizedException;
use App\Domain\Chat\Exceptions\RateLimitExceededException;
use App\Domain\Chat\Exceptions\RoomNotPostableException;
use App\Domain\Chat\Exceptions\UserMutedException;
use App\Domain\Chat\Http\Requests\PostMessageRequest;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\PolicyResolver;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-012, CHT-F-015
 */
class ChatMessageController extends Controller
{
    public function __construct(
        private readonly PostMessage $postMessage,
        private readonly DeleteMessage $deleteMessage,
        private readonly PolicyResolver $policyResolver,
    ) {}

    public function index(Request $request, ChatRoom $room): JsonResponse
    {
        $user = $request->user();
        $policy = $this->policyResolver->resolve($room);

        if (! $policy->canView($user, $room)) {
            throw new AuthorizationException;
        }

        $before = $request->string('before')->toString() ?: null;
        $limit = min(max((int) $request->integer('limit', 25), 1), 100);

        $query = $room->messages()->withTrashed()
            ->with('user:id,name,username')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($before !== null) {
            $cursor = ChatMessage::withTrashed()->find($before);
            if ($cursor !== null) {
                $query->where(function ($q) use ($cursor) {
                    $q->where('created_at', '<', $cursor->created_at)
                        ->orWhere(function ($qq) use ($cursor) {
                            $qq->where('created_at', '=', $cursor->created_at)
                                ->where('id', '<', $cursor->id);
                        });
                });
            }
        }

        $messages = $query->limit($limit)->get()->reverse()->values()->map(fn ($m) => [
            'id' => $m->id,
            'room_id' => $m->room_id,
            'user_id' => $m->user_id,
            'user' => [
                'id' => $m->user?->id,
                'name' => $m->user?->name,
                'username' => $m->user?->username,
            ],
            'body' => $m->body,
            'mentions' => $m->mentions_json ?? [],
            'deleted_at' => $m->deleted_at?->toIso8601String(),
            'created_at' => $m->created_at?->toIso8601String(),
        ])->all();

        return response()->json([
            'messages' => $messages,
            'has_more' => count($messages) === $limit,
        ]);
    }

    public function store(PostMessageRequest $request, ChatRoom $room): RedirectResponse
    {
        try {
            $this->postMessage->execute($request->user(), $room, $request->validated('body'));
        } catch (PostUnauthorizedException) {
            throw new AuthorizationException;
        } catch (RoomNotPostableException) {
            throw ValidationException::withMessages(['body' => __('chat.errors.room_not_postable')]);
        } catch (UserMutedException) {
            throw ValidationException::withMessages(['body' => __('chat.errors.user_muted')]);
        } catch (DuplicateMessageException) {
            throw ValidationException::withMessages(['body' => __('chat.errors.duplicate_message')]);
        } catch (RateLimitExceededException $e) {
            throw ValidationException::withMessages([
                'body' => __('chat.errors.rate_limit_'.$e->window),
            ]);
        }

        return back();
    }

    public function destroy(Request $request, ChatRoom $room, ChatMessage $message): RedirectResponse
    {
        if ($message->room_id !== $room->id) {
            throw new AuthorizationException;
        }

        $this->deleteMessage->execute($request->user(), $message);

        return back();
    }
}
