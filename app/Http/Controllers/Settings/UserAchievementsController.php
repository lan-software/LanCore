<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Achievements\Models\Achievement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserAchievementsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $earnedAchievements = $request->user()
            ->achievements()
            ->orderByPivot('earned_at', 'desc')
            ->get();

        $allAchievements = Achievement::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'icon']);

        return Inertia::render('settings/Achievements', [
            'earnedAchievements' => $earnedAchievements,
            'allAchievements' => $allAchievements,
        ]);
    }
}
