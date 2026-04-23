<?php

namespace App\Domain\Ticketing\Notifications;

use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Fired whenever a ticket's QR nonce rotates post-issuance (addUser,
 * removeUser, updateManager, explicit rotate). Sent to the ticket owner,
 * all currently-assigned users, and any previously-attached user or
 * manager that should know their printed copy is now invalid.
 *
 * Channels are always mail + database; security-relevant notifications
 * are not user-suppressible.
 *
 * @see docs/mil-std-498/SRS.md TKT-F-025
 */
class TicketTokenRotatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $reason,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventName = $this->ticket->event?->name ?? 'your event';
        $subject = 'Your ticket QR code has been updated';

        $message = (new MailMessage)
            ->subject($subject)
            ->line("The QR code for your ticket #{$this->ticket->id} ({$eventName}) has been regenerated.")
            ->line($this->reasonLine())
            ->line('Any previously printed or saved copies are no longer valid.');

        if ($this->ticket->id) {
            $message->action('View my ticket', route('tickets.show', $this->ticket));
        }

        return $message
            ->line('You can either re-download the PDF or show the live QR from "My Tickets" at the entrance.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'event_name' => $this->ticket->event?->name,
            'reason' => $this->reason,
        ];
    }

    private function reasonLine(): string
    {
        return match ($this->reason) {
            'user-added' => 'A user was added to this ticket, so the QR code has been refreshed.',
            'user-removed' => 'A user was removed from this ticket, so the QR code has been refreshed.',
            'manager-changed' => 'The ticket manager was changed, so the QR code has been refreshed.',
            'user-requested' => 'You (or the ticket manager) requested a QR refresh.',
            default => 'The QR code has been refreshed.',
        };
    }
}
