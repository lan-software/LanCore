<?php

namespace App\Domain\Event\Services;

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Ticketing\Enums\CheckInMode;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\EntranceAuditLog;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventDashboardStats
{
    /**
     * @return array<string, mixed>
     */
    public function forEvent(Event $event): array
    {
        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'start_date' => $event->start_date?->toISOString(),
                'end_date' => $event->end_date?->toISOString(),
                'status' => $event->status->value,
                'seat_capacity' => $event->seat_capacity,
            ],
            'headline' => $this->headline($event),
            'ticketTypes' => $this->ticketTypes($event),
            'seating' => $this->seating($event),
            'recentCheckins' => $this->recentCheckins($event),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function headline(Event $event): array
    {
        $ticketsSold = Ticket::query()
            ->where('event_id', $event->id)
            ->where('status', '!=', TicketStatus::Cancelled)
            ->count();

        $ticketsInSale = (int) TicketType::query()
            ->where('event_id', $event->id)
            ->withCount(['tickets' => fn ($q) => $q->where('status', '!=', TicketStatus::Cancelled)])
            ->get()
            ->sum(fn (TicketType $type) => max(0, (int) $type->quota - (int) $type->tickets_count));

        $seatedUserCount = SeatAssignment::forEvent($event->id)
            ->distinct('user_id')
            ->count('user_id');

        [$activeAssignees, $checkedIn] = $this->assigneeCounts($event);

        return [
            'ticketsSold' => $ticketsSold,
            'ticketsInSale' => $ticketsInSale,
            'seatedUserCount' => (int) $seatedUserCount,
            'checkedIn' => $checkedIn,
            'notCheckedIn' => max(0, $activeAssignees - $checkedIn),
            'activeAssignees' => $activeAssignees,
        ];
    }

    /**
     * @return array{int, int} [activeAssignees, checkedIn]
     */
    private function assigneeCounts(Event $event): array
    {
        $individualActive = Ticket::query()
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->where('tickets.event_id', $event->id)
            ->where('ticket_types.check_in_mode', CheckInMode::Individual->value)
            ->where('tickets.status', '!=', TicketStatus::Cancelled->value)
            ->count();

        $individualCheckedIn = Ticket::query()
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->where('tickets.event_id', $event->id)
            ->where('ticket_types.check_in_mode', CheckInMode::Individual->value)
            ->where('tickets.status', TicketStatus::CheckedIn->value)
            ->count();

        $groupActive = DB::table('ticket_user')
            ->join('tickets', 'ticket_user.ticket_id', '=', 'tickets.id')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->where('tickets.event_id', $event->id)
            ->where('ticket_types.check_in_mode', CheckInMode::Group->value)
            ->where('tickets.status', '!=', TicketStatus::Cancelled->value)
            ->count();

        $groupCheckedIn = DB::table('ticket_user')
            ->join('tickets', 'ticket_user.ticket_id', '=', 'tickets.id')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->where('tickets.event_id', $event->id)
            ->where('ticket_types.check_in_mode', CheckInMode::Group->value)
            ->where('tickets.status', '!=', TicketStatus::Cancelled->value)
            ->whereNotNull('ticket_user.checked_in_at')
            ->count();

        return [$individualActive + $groupActive, $individualCheckedIn + $groupCheckedIn];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ticketTypes(Event $event): array
    {
        return TicketType::query()
            ->where('event_id', $event->id)
            ->withCount(['tickets' => fn ($q) => $q->where('status', '!=', TicketStatus::Cancelled)])
            ->orderBy('name')
            ->get()
            ->map(fn (TicketType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'quota' => (int) $type->quota,
                'sold' => (int) $type->tickets_count,
                'remaining' => max(0, (int) $type->quota - (int) $type->tickets_count),
                'purchaseFrom' => $type->purchase_from?->toISOString(),
                'purchaseUntil' => $type->purchase_until?->toISOString(),
                'isOpenNow' => $type->isAvailableForPurchase(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function seating(Event $event): array
    {
        $checkedInUserIds = $this->checkedInUserIds($event);

        if ($checkedInUserIds->isEmpty()) {
            return ['seatedCheckedIn' => 0, 'unseatedCheckedIn' => 0];
        }

        $seatedUserIds = SeatAssignment::forEvent($event->id)
            ->whereIn('user_id', $checkedInUserIds->all())
            ->distinct('user_id')
            ->pluck('user_id');

        $seated = $seatedUserIds->count();
        $total = $checkedInUserIds->count();

        return [
            'seatedCheckedIn' => $seated,
            'unseatedCheckedIn' => max(0, $total - $seated),
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function checkedInUserIds(Event $event): Collection
    {
        $individualIds = Ticket::query()
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->where('tickets.event_id', $event->id)
            ->where('ticket_types.check_in_mode', CheckInMode::Individual->value)
            ->where('tickets.status', TicketStatus::CheckedIn->value)
            ->whereNotNull('tickets.owner_id')
            ->pluck('tickets.owner_id');

        $groupIds = DB::table('ticket_user')
            ->join('tickets', 'ticket_user.ticket_id', '=', 'tickets.id')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->where('tickets.event_id', $event->id)
            ->where('ticket_types.check_in_mode', CheckInMode::Group->value)
            ->where('tickets.status', '!=', TicketStatus::Cancelled->value)
            ->whereNotNull('ticket_user.checked_in_at')
            ->pluck('ticket_user.user_id');

        return $individualIds->merge($groupIds)->unique()->values();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentCheckins(Event $event, int $limit = 20): array
    {
        return EntranceAuditLog::query()
            ->join('tickets', 'entrance_audit_logs.ticket_id', '=', 'tickets.id')
            ->where('tickets.event_id', $event->id)
            ->whereIn('entrance_audit_logs.action', ['checkin', 'verify_checkin'])
            ->with([
                'ticket.ticketType:id,name',
                'ticket.owner:id,name',
            ])
            ->orderByDesc('entrance_audit_logs.created_at')
            ->select('entrance_audit_logs.*')
            ->limit($limit)
            ->get()
            ->map(fn (EntranceAuditLog $log) => [
                'id' => $log->id,
                'userName' => $log->ticket?->owner?->name,
                'ticketTypeName' => $log->ticket?->ticketType?->name,
                'action' => $log->action,
                'decision' => $log->decision,
                'at' => $log->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }
}
