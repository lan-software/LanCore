<?php

namespace App\Domain\Shop\Jobs;

use App\Domain\Shop\Mail\ReceiptMail;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\ShopSetting;
use App\Models\OrganizationSetting;
use App\Support\StorageRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class GenerateReceiptPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $orderId) {}

    public function handle(): void
    {
        $order = Order::with(['user', 'event', 'orderLines', 'voucher', 'confirmedBy'])->findOrFail($this->orderId);

        $org = OrganizationSetting::forInvoice();
        $currency = strtoupper((string) ($order->currency ?: 'eur'));
        $invoiceFooter = ShopSetting::get('invoice_footer', '');

        $pdf = Pdf::loadView('pdf.receipt', [
            'order' => $order,
            'org' => $org,
            'currency' => $currency,
            'invoiceFooter' => $invoiceFooter,
        ]);

        $path = "receipts/{$order->id}.pdf";
        StorageRole::private()->put($path, $pdf->output());

        Mail::to($order->user->email)->send(new ReceiptMail($order, $path));
    }
}
