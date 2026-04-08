<?php

namespace App\Domain\Shop\Jobs;

use App\Domain\Shop\Mail\InvoiceMail;
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

class GenerateInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $orderId) {}

    public function handle(): void
    {
        $order = Order::with(['user', 'event', 'orderLines', 'voucher'])->findOrFail($this->orderId);

        $org = OrganizationSetting::forInvoice();
        $currency = strtoupper(config('cashier.currency', 'eur'));
        $invoiceFooter = ShopSetting::get('invoice_footer', '');
        $invoiceNotes = ShopSetting::get('invoice_notes', '');

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'org' => $org,
            'currency' => $currency,
            'invoiceFooter' => $invoiceFooter,
            'invoiceNotes' => $invoiceNotes,
        ]);

        $path = "invoices/{$order->id}.pdf";
        StorageRole::private()->put($path, $pdf->output());

        Mail::to($order->user->email)->send(new InvoiceMail($order, $path));
    }
}
