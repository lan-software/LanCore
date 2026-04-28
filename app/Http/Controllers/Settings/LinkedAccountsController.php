<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LinkedAccountsController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('settings/LinkedAccounts', [
            'steam' => [
                'linked' => $user->hasSteam(),
                'steam_id_64' => $user->steam_id_64,
                'linked_at' => $user->steam_linked_at?->toIso8601String(),
                'profile_url' => $user->steam_id_64 !== null
                    ? sprintf('https://steamcommunity.com/profiles/%s', $user->steam_id_64)
                    : null,
            ],
            'canUnlink' => $user->hasUsablePassword(),
        ]);
    }
}
