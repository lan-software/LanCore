<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserAchievementsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $achievements = $request->user()
            ->achievements()
            ->orderByPivot('earned_at', 'desc')
            ->get();

        return Inertia::render('settings/Achievements', [
            'achievements' => $achievements,
        ]);
    }
}
