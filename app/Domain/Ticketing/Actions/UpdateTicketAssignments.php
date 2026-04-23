<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Seating\Actions\ReleaseSeat;
use App\Domain\Ticketing\Enums\CheckInMode;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Jobs\GenerateTicketPdf;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Notifications\TicketTokenRotatedNotification;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-005, CAP-TKT-011, CAP-TKT-012, CAP-TKT-015
 * @see docs/mil-std-498/SRS.md TKT-F-004, TKT-F-006, TKT-F-014, TKT-F-015, TKT-F-019, TKT-F-024, TKT-F-025
 */
class UpdateTicketAssignments
{
    public function __construct(
        private readonly TicketTokenService $tokenService,
        private readonly ReleaseSeat $releaseSeat,
    ) {}

    public function updateManager(Ticket $ticket, ?User $manager, int $performedBy): Ticket
    {
        $this->ensureNotCheckedIn($ticket);

        $ticket->loadMissing('manager');
        $previousManager = $ticket->manager;

        [$result, $payload] = DB::transaction(function () use ($ticket, $manager): array {
            $ticket->update(['manager_id' => $manager?->id]);
            $payload = $this->rotateTokenInternal($ticket);

            return [$ticket->fresh(), $payload];
        });

        $this->dispatchPdf($result, $payload);
        $this->notifyRotation($result, 'manager-changed', array_filter([$previousManager]));

        return $result;
    }

    public function addUser(Ticket $ticket, User $user, int $performedBy): Ticket
    {
        $this->ensureNotCheckedIn($ticket);

        [$result, $payload] = DB::transaction(function () use ($ticket, $user): array {
            $maxUsers = $ticket->ticketType->max_users_per_ticket;
            $currentCount = $ticket->users()->count();

            if ($currentCount >= $maxUsers) {
                throw new InvalidArgumentException('This ticket has reached its maximum number of assigned users.');
            }

            $ticket->users()->attach($user->id);
            $payload = $this->rotateTokenInternal($ticket);

            return [$ticket->fresh(), $payload];
        });

        $this->dispatchPdf($result, $payload);
        $this->notifyRotation($result, 'user-added');

        return $result;
    }

    public function removeUser(Ticket $ticket, User $user, int $performedBy): Ticket
    {
        $this->ensureNotCheckedIn($ticket);

        [$result, $payload] = DB::transaction(function () use ($ticket, $user): array {
            $this->releaseSeat->execute($ticket, $user);
            $ticket->users()->detach($user->id);
            $payload = $this->rotateTokenInternal($ticket);

            return [$ticket->fresh(), $payload];
        });

        $this->dispatchPdf($result, $payload);
        $this->notifyRotation($result, 'user-removed', [$user]);

        return $result;
    }

    public function rotateToken(Ticket $ticket): void
    {
        $this->ensureNotCheckedIn($ticket);

        $payload = $this->rotateTokenInternal($ticket);
        $fresh = $ticket->fresh() ?? $ticket;

        $this->dispatchPdf($fresh, $payload);
        $this->notifyRotation($fresh, 'user-requested');
    }

    private function rotateTokenInternal(Ticket $ticket): string
    {
        return $ticket->rotateSignedToken($this->tokenService);
    }

    public function checkIn(Ticket $ticket, int $performedBy, ?int $userId = null): Ticket
    {
        $this->ensureNotCheckedIn($ticket);

        return $this->performCheckIn($ticket, $userId);
    }

    private function performCheckIn(Ticket $ticket, ?int $userId = null): Ticket
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
                    $assignedUsers = $ticket->users;

                    if ($assignedUsers->count() === 1) {
                        $userId = $assignedUsers->first()->id;
                    } elseif ($assignedUsers->isEmpty()) {
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

    private function dispatchPdf(Ticket $ticket, string $qrPayload): void
    {
        GenerateTicketPdf::dispatch($ticket->id, $qrPayload);
    }

    /**
     * @param  array<int, ?User>|Collection<int, User>  $extraUsers
     */
    private function notifyRotation(Ticket $ticket, string $reason, iterable $extraUsers = []): void
    {
        $ticket->loadMissing(['owner', 'users', 'event']);

        $recipients = collect([$ticket->owner])
            ->merge($ticket->users)
            ->merge($extraUsers)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TicketTokenRotatedNotification($ticket, $reason));
    }

    private function invalidateToken(Ticket $ticket): void
    {
        $ticket->forceFill([
            'validation_nonce_hash' => null,
            'validation_kid' => null,
            'validation_issued_at' => null,
            'validation_expires_at' => null,
        ])->save();
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
