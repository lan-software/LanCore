<?php

namespace App\Domain\Ticketing\Jobs;

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\OrganizationSetting;
use App\Support\StorageRole;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateTicketPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $ticketId,
        private readonly string $qrPayload,
    ) {}

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

        $qrBase64 = $this->generateQrCode($this->qrPayload);
        $org = OrganizationSetting::forInvoice();
        $bannerBase64 = $this->bannerToBase64($ticket->event?->banner_images[0] ?? null);
        $watermarkBase64 = $this->generateWatermarkBase64($ticket, $org);
        $conditions = GlobalPurchaseCondition::activeOrdered()->get();

        $pdf = Pdf::loadView('pdf.ticket', [
            'ticket' => $ticket,
            'qrCode' => $qrBase64,
            'org' => $org,
            'bannerBase64' => $bannerBase64,
            'watermarkBase64' => $watermarkBase64,
            'conditions' => $conditions,
        ])->setPaper('a4', 'portrait');

        $path = "tickets/{$ticket->id}.pdf";
        StorageRole::private()->put($path, $pdf->output());
    }

    private function generateQrCode(string $data): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd,
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($data);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Crop the banner to the hero's aspect ratio (186mm × 62mm ≈ 3:1)
     * server-side via GD, then base64-encode. We do the crop here rather
     * than rely on CSS `object-fit: cover` or `background-size: cover`,
     * which DomPDF 3.x does not render reliably (the image ends up either
     * stretched or missing entirely).
     */
    private function bannerToBase64(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $disk = StorageRole::public();

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);
        $src = @imagecreatefromstring($contents);

        if ($src === false) {
            return null;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $targetRatio = 186 / 62;
        $srcRatio = $srcW / $srcH;

        if ($srcRatio > $targetRatio) {
            $newW = (int) round($srcH * $targetRatio);
            $newH = $srcH;
            $srcX = (int) round(($srcW - $newW) / 2);
            $srcY = 0;
        } else {
            $newW = $srcW;
            $newH = (int) round($srcW / $targetRatio);
            $srcX = 0;
            $srcY = (int) round(($srcH - $newH) / 2);
        }

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopy($dst, $src, 0, 0, $srcX, $srcY, $newW, $newH);

        ob_start();
        imagejpeg($dst, null, 82);
        $cropped = ob_get_clean();

        if (! is_string($cropped) || $cropped === '') {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($cropped);
    }

    /**
     * Build a personalised PNG watermark overlay for the A4 page.
     *
     * The watermark carries attendee + event + org identity at ~12% opacity,
     * repeated diagonally across the page with per-ticket angle/offset
     * variance (seeded by ticket id). A safe rectangle around the middle-
     * panel QR is left blank so scanners see a clean code.
     *
     * Returns null gracefully when GD TTF support or the DejaVu font is
     * unavailable — the Blade guards the <img> with @if.
     *
     * NOTE: the QR safe zone coordinates below assume the current tri-fold
     * layout in resources/views/pdf/ticket.blade.php. If the panel layout
     * changes, revisit these coordinates.
     *
     * @param  array<string, mixed>  $org
     */
    private function generateWatermarkBase64(Ticket $ticket, array $org): ?string
    {
        $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

        if (! function_exists('imagettftext') || ! is_file($fontPath)) {
            return null;
        }

        $canvasW = 1240;
        $canvasH = 1754;
        $fontSize = 12;

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        // ~12% visible = alpha 112/127 (lower alpha = more opaque in GD).
        $textColor = imagecolorallocatealpha($canvas, 0, 0, 0, 112);

        $owner = Str::limit((string) ($ticket->owner?->name ?? 'Unassigned'), 30, '…');
        $event = Str::limit((string) ($ticket->event?->name ?? 'Event'), 30, '…');
        $venue = (string) ($ticket->event?->venue?->name ?? 'Venue');
        $city = (string) ($ticket->event?->venue?->address?->city ?? '');
        $location = Str::limit($city !== '' ? "{$venue}, {$city}" : $venue, 40, '…');
        $orgName = Str::limit((string) ($org['name'] ?? 'Organisation'), 30, '…');
        $ticketTypeName = Str::limit((string) ($ticket->ticketType?->name ?? 'Ticket'), 30, '…');

        $text = "{$owner} · {$event} · {$location} · {$orgName} · {$ticketTypeName} · #{$ticket->id}";

        mt_srand($ticket->id);
        $angle = 45.0 + (mt_rand(0, 10) - 5);
        $offsetX = mt_rand(0, 180);
        $offsetY = mt_rand(0, 180);

        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textW = $bbox[2] - $bbox[0];
        $textH = abs($bbox[7] - $bbox[1]);

        // QR safe zone (middle-panel, left column). Pixels at 150 DPI.
        // 5mm..80mm horizontally, 105mm..190mm vertically.
        $safeLeft = (int) round(5 / 25.4 * 150);     // ≈ 30
        $safeTop = (int) round(105 / 25.4 * 150);    // ≈ 620
        $safeRight = (int) round(80 / 25.4 * 150);   // ≈ 473
        $safeBottom = (int) round(190 / 25.4 * 150); // ≈ 1122

        $rowStep = max((int) round($textH * 5), 48);
        $colStep = $textW + 60;

        $rad = deg2rad($angle);
        $cos = cos($rad);
        $sin = sin($rad);

        for ($y = -$rowStep + $offsetY; $y < $canvasH + $rowStep; $y += $rowStep) {
            for ($x = -$colStep + $offsetX; $x < $canvasW + $colStep; $x += $colStep) {
                // Compute rotated bounding box of this instance.
                $corners = [
                    [0, 0],
                    [$textW, 0],
                    [$textW, -$textH],
                    [0, -$textH],
                ];
                $minX = PHP_INT_MAX;
                $maxX = PHP_INT_MIN;
                $minY = PHP_INT_MAX;
                $maxY = PHP_INT_MIN;
                foreach ($corners as [$cx, $cy]) {
                    $rx = $x + ($cx * $cos - $cy * $sin);
                    $ry = $y + ($cx * $sin + $cy * $cos);
                    $minX = min($minX, $rx);
                    $maxX = max($maxX, $rx);
                    $minY = min($minY, $ry);
                    $maxY = max($maxY, $ry);
                }

                // Skip if bounding box overlaps the QR safe zone.
                if ($maxX >= $safeLeft && $minX <= $safeRight
                    && $maxY >= $safeTop && $minY <= $safeBottom
                ) {
                    continue;
                }

                imagettftext($canvas, $fontSize, $angle, $x, $y, $textColor, $fontPath, $text);
            }
        }

        ob_start();
        imagepng($canvas, null, 9);
        $png = ob_get_clean();

        if (! is_string($png) || $png === '') {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
