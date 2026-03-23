<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Shop\Http\Requests\OrderIndexRequest;
use App\Domain\Shop\Models\Order;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(OrderIndexRequest $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['user', 'event', 'voucher']);

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%"));
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
}
