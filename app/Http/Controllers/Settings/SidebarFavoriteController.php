<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SidebarFavoriteController extends Controller
{
    public function toggle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $favorites = $user->sidebar_favorites ?? [];
        $itemId = $validated['item_id'];

        if (in_array($itemId, $favorites, true)) {
            $favorites = array_values(array_filter($favorites, fn (string $id): bool => $id !== $itemId));
        } else {
            $favorites[] = $itemId;
        }

        $user->update(['sidebar_favorites' => $favorites]);

        return back();
    }
}
