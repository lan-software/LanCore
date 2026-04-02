<?php

namespace App\Domain\Ticketing\Http\Controllers;

use App\Domain\Ticketing\Http\Requests\AdminTicketIndexRequest;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md TKT-F-010
 */
class AdminTicketController extends Controller
{
    public function index(AdminTicketIndexRequest $request): Response
    {
        $this->authorize('viewAny', Ticket::class);

        $query = Ticket::with(['ticketType', 'event', 'owner', 'order']);

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('validation_id', 'ilike', "%{$search}%")
                    ->orWhereHas('owner', fn ($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%"))
                    ->orWhereHas('ticketType', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));
            });
        }

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $tickets = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('admin-tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'sort', 'direction', 'status', 'per_page']),
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'ticketType.ticketCategory',
            'event',
            'order.user',
            'owner',
            'manager',
            'ticketUser',
            'addons',
        ]);

        return Inertia::render('admin-tickets/Show', [
            'ticket' => $ticket,
        ]);
    }
}
