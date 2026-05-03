<?php

namespace App\Domain\DataLifecycle\Mail;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeletionScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly CarbonInterface $scheduledFor,
        public readonly string $cancelUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account deletion scheduled — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.data-lifecycle.deletion-scheduled',
            with: [
                'subject' => $this->user,
                'scheduledFor' => $this->scheduledFor,
                'cancelUrl' => $this->cancelUrl,
            ],
        );
    }
}
