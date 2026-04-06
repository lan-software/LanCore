<?php

namespace App\Domain\Ticketing\Jobs;

use App\Domain\Ticketing\Models\Ticket;
use App\Models\OrganizationSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateTicketPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $ticketId) {}

    public function handle(): void
    {
        $ticket = Ticket::with([
            'ticketType',
            'event.venue.address',
            'owner',
            'manager',
            'users',
            'addons',
            'order',
        ])->findOrFail($this->ticketId);

        $qrBase64 = $this->generateQrCode($ticket->validation_id);
        $org = OrganizationSetting::forInvoice();

        $pdf = Pdf::loadView('pdf.ticket', [
            'ticket' => $ticket,
            'qrCode' => $qrBase64,
            'org' => $org,
        ]);

        $path = "tickets/{$ticket->id}.pdf";
        Storage::disk('local')->put($path, $pdf->output());
    }

    private function generateQrCode(string $data): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd(),
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($data);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
