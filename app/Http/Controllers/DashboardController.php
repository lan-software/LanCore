<?php

namespace App\Http\Controllers;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\Voucher;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
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
                'tickets' => Ticket::count(),
                'ticket_types' => TicketType::count(),
                'addons' => Addon::count(),
                'orders' => Order::count(),
                'games' => Game::count(),
                'game_modes' => GameMode::count(),
                'seat_plans' => SeatPlan::count(),
                'vouchers' => Voucher::count(),
            ],
            'events' => [
                'upcoming' => Event::upcoming()->count(),
                'past' => Event::where('end_date', '<', now())->count(),
                'published' => Event::where('status', EventStatus::Published)->count(),
                'draft' => Event::where('status', EventStatus::Draft)->count(),
            ],
            'tickets' => [
                'active' => Ticket::where('status', TicketStatus::Active)->count(),
                'checked_in' => Ticket::where('status', TicketStatus::CheckedIn)->count(),
                'cancelled' => Ticket::where('status', TicketStatus::Cancelled)->count(),
            ],
            'orders' => [
                'pending' => Order::where('status', OrderStatus::Pending)->count(),
                'completed' => Order::where('status', OrderStatus::Completed)->count(),
                'failed' => Order::where('status', OrderStatus::Failed)->count(),
                'refunded' => Order::where('status', OrderStatus::Refunded)->count(),
                'total_revenue' => Order::where('status', OrderStatus::Completed)->sum('total'),
            ],
            'roles' => $roleCounts->all(),
            'lastActiveUsers' => $lastActiveUsers,
        ];
    }
}
