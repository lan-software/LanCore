<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Enums\CheckInMode;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Jobs\GenerateTicketPdf;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-005, CAP-TKT-011, CAP-TKT-012
 * @see docs/mil-std-498/SRS.md TKT-F-004, TKT-F-006, TKT-F-014, TKT-F-015
 */
class UpdateTicketAssignments
{
    public function updateManager(Ticket $ticket, ?User $manager, int $performedBy): Ticket
    {
        $this->ensureNotCheckedIn($ticket);

        $result = DB::transaction(function () use ($ticket, $manager): Ticket {
            $ticket->update(['manager_id' => $manager?->id]);

            return $ticket;
        });

        GenerateTicketPdf::dispatch($ticket->id);

        return $result;
    }

    public function addUser(Ticket $ticket, User $user, int $performedBy): Ticket
    {
        $this->ensureNotCheckedIn($ticket);

        $result = DB::transaction(function () use ($ticket, $user): Ticket {
            $maxUsers = $ticket->ticketType->max_users_per_ticket;
            $currentCount = $ticket->users()->count();

            if ($currentCount >= $maxUsers) {
                throw new InvalidArgumentException('This ticket has reached its maximum number of assigned users.');
            }

            $ticket->users()->attach($user->id);

            return $ticket->fresh();
        });

        GenerateTicketPdf::dispatch($ticket->id);

        return $result;
    }

    public function removeUser(Ticket $ticket, User $user, int $performedBy): Ticket
    {
        $this->ensureNotCheckedIn($ticket);

        $result = DB::transaction(function () use ($ticket, $user): Ticket {
            $ticket->users()->detach($user->id);

            return $ticket->fresh();
        });

        GenerateTicketPdf::dispatch($ticket->id);

        return $result;
    }

    public function checkIn(Ticket $ticket, int $performedBy, ?int $userId = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $userId): Ticket {
            $checkInMode = $ticket->ticketType->check_in_mode;

            if ($checkInMode === CheckInMode::Group) {
                $ticket->users()->each(function (User $user) use ($ticket) {
                    $ticket->users()->updateExistingPivot($user->id, ['checked_in_at' => now()]);
                });

                $ticket->update([
                    'status' => TicketStatus::CheckedIn,
                    'checked_in_at' => now(),
                ]);
            } else {
                if ($userId === null) {
                    // For single-user tickets, check in the only assigned user (or ticket itself)
                    $assignedUsers = $ticket->users;

                    if ($assignedUsers->count() === 1) {
                        $userId = $assignedUsers->first()->id;
                    } elseif ($assignedUsers->isEmpty()) {
                        // No users assigned — just check in the ticket directly
                        $ticket->update([
                            'status' => TicketStatus::CheckedIn,
                            'checked_in_at' => now(),
                        ]);

                        return $ticket->fresh();
                    } else {
                        throw new InvalidArgumentException('User ID is required for individual check-in on group tickets.');
                    }
                }

                $ticket->users()->updateExistingPivot($userId, ['checked_in_at' => now()]);

                $allCheckedIn = $ticket->users()->whereNull('ticket_user.checked_in_at')->count() === 0;

                if ($allCheckedIn) {
                    $ticket->update([
                        'status' => TicketStatus::CheckedIn,
                        'checked_in_at' => now(),
                    ]);
                }
            }

            return $ticket->fresh();
        });
    }

    private function ensureNotCheckedIn(Ticket $ticket): void
    {
        if ($ticket->checked_in_at !== null) {
            throw ValidationException::withMessages([
                'ticket' => 'This ticket has been checked in and can no longer be modified.',
            ]);
        }
    }
}
