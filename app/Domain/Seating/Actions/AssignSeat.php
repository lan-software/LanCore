<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Assign (or move) a single seat for one user on one ticket.
 *
 * Authorization is the caller's responsibility — typically `TicketPolicy::pickSeat`.
 *
 * @see docs/mil-std-498/SRS.md SET-F-006, SET-F-007
 * @see docs/mil-std-498/SDD.md §3.6 Seating
 */
class AssignSeat
{
    public function execute(Ticket $ticket, User $assignee, SeatPlan $seatPlan, string $seatId): SeatAssignment
    {
        $this->ensureSeatPlanBelongsToTicketEvent($ticket, $seatPlan);
        $this->ensureSeatExistsAndIsSalable($seatPlan, $seatId);
        $this->ensureAssigneeIsOnTicket($ticket, $assignee);

        try {
            return DB::transaction(function () use ($ticket, $assignee, $seatPlan, $seatId): SeatAssignment {
                return SeatAssignment::updateOrCreate(
                    ['ticket_id' => $ticket->id, 'user_id' => $assignee->id],
                    ['seat_plan_id' => $seatPlan->id, 'seat_id' => $seatId],
                );
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'seat_id' => __('seating.errors.seat_taken'),
                ]);
            }

            throw $exception;
        }
    }

    private function ensureSeatPlanBelongsToTicketEvent(Ticket $ticket, SeatPlan $seatPlan): void
    {
        if ($seatPlan->event_id !== $ticket->event_id) {
            throw ValidationException::withMessages([
                'seat_plan_id' => __('seating.errors.seat_plan_event_mismatch'),
            ]);
        }
    }

    private function ensureSeatExistsAndIsSalable(SeatPlan $seatPlan, string $seatId): void
    {
        $blocks = $seatPlan->data['blocks'] ?? [];

        foreach ($blocks as $block) {
            foreach ($block['seats'] ?? [] as $seat) {
                if ((string) ($seat['id'] ?? '') === $seatId) {
                    if (! ($seat['salable'] ?? false)) {
                        throw ValidationException::withMessages([
                            'seat_id' => __('seating.errors.seat_not_available'),
                        ]);
                    }

                    return;
                }
            }
        }

        throw ValidationException::withMessages([
            'seat_id' => __('seating.errors.seat_not_found'),
        ]);
    }

    private function ensureAssigneeIsOnTicket(Ticket $ticket, User $assignee): void
    {
        if ($ticket->owner_id === $assignee->id) {
            return;
        }

        if ($ticket->users()->whereKey($assignee->id)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'user_id' => __('seating.errors.user_not_on_ticket'),
        ]);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return $exception->getCode() === '23000' || $exception->getCode() === '23505';
    }
}
