<?php

namespace App\Domain\DataLifecycle\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeletionCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $subject) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account deletion cancelled — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.data-lifecycle.deletion-cancelled',
            with: ['subject' => $this->subject],
        );
    }
}
