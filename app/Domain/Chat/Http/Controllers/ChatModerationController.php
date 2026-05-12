<?php

namespace App\Domain\Chat\Http\Controllers;

use App\Domain\Chat\Actions\CloseRoom;
use App\Domain\Chat\Actions\MuteUser;
use App\Domain\Chat\Actions\ReopenRoom;
use App\Domain\Chat\Actions\UnmuteUser;
use App\Domain\Chat\Models\ChatRoom;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-020..023
 */
class ChatModerationController extends Controller
{
    public function __construct(
        private readonly MuteUser $muteUser,
        private readonly UnmuteUser $unmuteUser,
        private readonly CloseRoom $closeRoom,
        private readonly ReopenRoom $reopenRoom,
    ) {}

    public function mute(Request $request, ChatRoom $room, User $user): RedirectResponse
    {
        $data = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $until = now()->addMinutes((int) $data['minutes']);
        $this->muteUser->execute($request->user(), $room, $user, $until, $data['reason'] ?? null);

        return back();
    }

    public function unmute(Request $request, ChatRoom $room, User $user): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;
        $this->unmuteUser->execute($request->user(), $room, $user, $reason);

        return back();
    }

    public function close(Request $request, ChatRoom $room): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;
        $this->closeRoom->execute($request->user(), $room, $reason);

        return back();
    }

    public function reopen(Request $request, ChatRoom $room): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;
        $this->reopenRoom->execute($request->user(), $room, $reason);

        return back();
    }
}
