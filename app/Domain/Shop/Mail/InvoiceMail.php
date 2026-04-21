<?php

namespace App\Domain\Shop\Mail;

use App\Domain\Shop\Models\Order;
use App\Support\StorageRole;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->order->invoice_number} — " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice',
            with: [
                'order' => $this->order,
                'invoiceNumber' => $this->order->invoice_number,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(StorageRole::privateDiskName(), $this->pdfPath)
                ->as("invoice-{$this->order->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
