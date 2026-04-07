<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Shop\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-017
 */
class UserOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedEventId = $request->session()->get('my_selected_event_id');

        $orders = $request->user()
            ->orders()
            ->when($selectedEventId, fn ($q) => $q->where('event_id', $selectedEventId))
            ->with(['event', 'tickets.ticketType', 'orderLines'])
            ->latest()
            ->get();

        return Inertia::render('my-orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load([
            'event',
            'voucher',
            'tickets.ticketType',
            'tickets.event',
            'tickets.owner',
            'tickets.manager',
            'tickets.users',
            'tickets.addons',
            'orderLines',
        ]);

        return Inertia::render('my-orders/Show', [
            'order' => $order,
        ]);
    }
}
