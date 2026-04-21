<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CookiePreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['stored' => false], 204);
        }

        $validated = $request->validate([
            'categories' => ['required', 'array'],
            'categories.*' => ['string', 'in:necessary,analytics'],
            'revision' => ['nullable', 'integer'],
        ]);

        $user->cookie_preferences = [
            'categories' => array_values(array_unique($validated['categories'])),
            'revision' => $validated['revision'] ?? 1,
            'updated_at' => now()->toISOString(),
        ];
        $user->save();

        return response()->json(['stored' => true]);
    }
}
