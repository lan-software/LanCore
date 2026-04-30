<?php

namespace App\Domain\DataLifecycle\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeletionConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $subject,
        public readonly string $confirmUrl,
        public readonly string $cancelUrl,
        public readonly int $graceDays = 30,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm account deletion — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.data-lifecycle.deletion-confirmation',
            with: [
                'subject' => $this->subject,
                'confirmUrl' => $this->confirmUrl,
                'cancelUrl' => $this->cancelUrl,
                'graceDays' => $this->graceDays,
            ],
        );
    }
}
