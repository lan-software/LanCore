<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanSeat;
use App\Domain\Seating\Support\SeatingCategoryRules;
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
 * @see docs/mil-std-498/SDD.md §5.3c Seating
 */
class AssignSeat
{
    public function execute(Ticket $ticket, User $assignee, SeatPlan $seatPlan, int $seatId): SeatAssignment
    {
        $this->ensureSeatPlanBelongsToTicketEvent($ticket, $seatPlan);
        $seat = $this->ensureSeatExistsAndIsSalable($seatPlan, $seatId);
        $this->ensureAssigneeIsOnTicket($ticket, $assignee);
        $this->ensureBlockAcceptsCategory($ticket, $seat);

        try {
            return DB::transaction(function () use ($ticket, $assignee, $seatPlan, $seat): SeatAssignment {
                return SeatAssignment::updateOrCreate(
                    ['ticket_id' => $ticket->id, 'user_id' => $assignee->id],
                    ['seat_plan_id' => $seatPlan->id, 'seat_plan_seat_id' => $seat->id],
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

    private function ensureSeatExistsAndIsSalable(SeatPlan $seatPlan, int $seatId): SeatPlanSeat
    {
        $seat = $seatPlan->seats()
            ->with(['block.categoryRestrictions'])
            ->whereKey($seatId)
            ->first();

        if ($seat === null) {
            throw ValidationException::withMessages([
                'seat_id' => __('seating.errors.seat_not_found'),
            ]);
        }

        if (! $seat->salable) {
            throw ValidationException::withMessages([
                'seat_id' => __('seating.errors.seat_not_available'),
            ]);
        }

        return $seat;
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

    /**
     * Enforce per-block ticket-category restrictions (SET-F-011). A ValidationException
     * (not 403) is surfaced — the user has the right to act on the ticket; they just
     * aimed at the wrong block.
     */
    private function ensureBlockAcceptsCategory(Ticket $ticket, SeatPlanSeat $seat): void
    {
        $block = $seat->block;

        if ($block === null) {
            return;
        }

        $ticket->loadMissing('ticketType');
        $categoryId = $ticket->ticketType?->ticket_category_id;

        if (SeatingCategoryRules::blockAccepts($block, $categoryId)) {
            return;
        }

        throw ValidationException::withMessages([
            'seat_id' => __('seating.errors.block_category_forbidden'),
        ]);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return $exception->getCode() === '23000' || $exception->getCode() === '23505';
    }
}
