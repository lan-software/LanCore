<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Http\Requests\OrderIndexRequest;
use App\Domain\Shop\Jobs\GenerateReceiptPdf;
use App\Domain\Shop\Models\Order;
use App\Http\Controllers\Controller;
use App\Support\StorageRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-004
 * @see docs/mil-std-498/SRS.md SHP-F-004, SHP-F-006, SHP-F-014
 */
class OrderController extends Controller
{
    public function index(OrderIndexRequest $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['user', 'event', 'voucher']);

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q) => $q->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"));
            });
        }

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        if ($paymentMethod = $request->validated('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $orders = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'sort', 'direction', 'status', 'payment_method', 'per_page']),
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load([
            'user',
            'event',
            'voucher',
            'tickets.ticketType',
            'tickets.owner',
            'orderLines',
        ]);

        return Inertia::render('orders/Show', [
            'order' => $order,
        ]);
    }

    public function confirmPayment(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('confirmPayment', $order);

        if ($order->payment_method !== PaymentMethod::OnSite) {
            return back()->withErrors(['order' => 'Only on-site orders can be manually confirmed.']);
        }

        if ($order->paid_at !== null) {
            return back()->withErrors(['order' => 'This order has already been marked as paid.']);
        }

        $order->update([
            'paid_at' => now(),
            'confirmed_by' => $request->user()->id,
        ]);

        GenerateReceiptPdf::dispatch($order->id);

        return back();
    }

    public function downloadInvoice(Order $order): StreamedResponse
    {
        $this->authorize('view', $order);

        $path = "invoices/{$order->id}.pdf";

        abort_unless(StorageRole::private()->exists($path), 404, 'Invoice not yet generated.');

        return StorageRole::private()->download($path, "invoice-{$order->invoice_number}.pdf");
    }

    public function downloadReceipt(Order $order): StreamedResponse
    {
        $this->authorize('view', $order);

        $path = "receipts/{$order->id}.pdf";

        abort_unless(StorageRole::private()->exists($path), 404, 'Receipt not yet generated.');

        return StorageRole::private()->download($path, "receipt-{$order->invoice_number}.pdf");
    }
}
