<?php

namespace App\Http\Controllers;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Domain\Venue\Models\Venue;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();

        $isAdmin = $user->isAdmin();

        $stats = [];

        if ($isAdmin) {
            $stats = $this->getAdminStats();
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getAdminStats(): array
    {
        $roleCounts = Role::withCount('users')->get()->mapWithKeys(
            fn (Role $role): array => [$role->name->value => $role->users_count]
        );

        $recentSessions = DB::table('sessions')
            ->whereNotNull('user_id')
            ->orderByDesc('last_activity')
            ->limit(3)
            ->get(['user_id', 'last_activity']);

        $recentUserIds = $recentSessions->pluck('user_id')->unique()->values();
        $recentUsers = User::whereIn('id', $recentUserIds)->get()->keyBy('id');

        $lastActiveUsers = $recentSessions->map(fn ($session): array => [
            'id' => $session->user_id,
            'name' => $recentUsers[$session->user_id]?->name ?? 'Unknown',
            'email' => $recentUsers[$session->user_id]?->email ?? '',
            'last_activity' => date('c', $session->last_activity),
        ])->values()->all();

        return [
            'counts' => [
                'users' => User::count(),
                'events' => Event::count(),
                'programs' => Program::count(),
                'time_slots' => TimeSlot::count(),
                'venues' => Venue::count(),
                'sponsors' => Sponsor::count(),
                'sponsor_levels' => SponsorLevel::count(),
            ],
            'events' => [
                'upcoming' => Event::upcoming()->count(),
                'past' => Event::where('end_date', '<', now())->count(),
                'published' => Event::where('status', EventStatus::Published)->count(),
                'draft' => Event::where('status', EventStatus::Draft)->count(),
            ],
            'roles' => $roleCounts->all(),
            'lastActiveUsers' => $lastActiveUsers,
        ];
    }
}
