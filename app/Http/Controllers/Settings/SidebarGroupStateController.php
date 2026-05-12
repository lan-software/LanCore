<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Persists per-user collapse state for sidebar groups. Mirrors the
 * `SidebarFavoriteController` model — one POST endpoint that flips the
 * group id in the user's stored list.
 */
class SidebarGroupStateController extends Controller
{
    public function toggle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_id' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $collapsed = $user->sidebar_collapsed_groups ?? [];
        $groupId = $validated['group_id'];

        if (in_array($groupId, $collapsed, true)) {
            $collapsed = array_values(array_filter($collapsed, fn (string $id): bool => $id !== $groupId));
        } else {
            $collapsed[] = $groupId;
        }

        $user->update(['sidebar_collapsed_groups' => $collapsed]);

        return back();
    }
}
