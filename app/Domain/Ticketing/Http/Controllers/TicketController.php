<?php

namespace App\Domain\Ticketing\Http\Controllers;

use App\Domain\Ticketing\Actions\UpdateTicketAssignments;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-005, CAP-TKT-006
 * @see docs/mil-std-498/SRS.md TKT-F-004, TKT-F-005, TKT-F-006
 */
class TicketController extends Controller
{
    public function __construct(
        private readonly UpdateTicketAssignments $updateTicketAssignments,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $ownedTickets = $user->ownedTickets()
            ->with(['ticketType', 'event', 'manager', 'ticketUser', 'addons', 'order'])
            ->get();

        $managedTickets = $user->managedTickets()
            ->where('owner_id', '!=', $user->id)
            ->with(['ticketType', 'event', 'owner', 'ticketUser', 'addons', 'order'])
            ->get();

        $usableTickets = $user->usableTickets()
            ->where('owner_id', '!=', $user->id)
            ->where(function ($query) use ($user) {
                $query->where('manager_id', '!=', $user->id)
                    ->orWhereNull('manager_id');
            })
            ->with(['ticketType', 'event', 'addons', 'order'])
            ->get();

        return Inertia::render('tickets/Index', [
            'ownedTickets' => $this->enrichTickets($ownedTickets),
            'managedTickets' => $this->enrichTickets($managedTickets),
            'usableTickets' => $this->enrichTickets($usableTickets),
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'ticketType.ticketCategory',
            'event',
            'order',
            'owner',
            'manager',
            'ticketUser',
            'addons',
        ]);

        $ticketData = $this->enrichTickets(collect([$ticket]))[0];

        return Inertia::render('tickets/Show', [
            'ticket' => $ticketData,
            'canUpdateManager' => Gate::allows('updateManager', $ticket),
            'canUpdateUser' => Gate::allows('updateUser', $ticket),
        ]);
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return array<int, array<string, mixed>>
     */
    private function enrichTickets(Collection $tickets): array
    {
        return $tickets->map(function (Ticket $ticket): array {
            $data = $ticket->toArray();

            if ($ticket->event) {
                $bannerImages = array_values(array_filter($ticket->event->banner_images ?? [], fn ($p) => is_string($p) && $p !== ''));
                $data['event']['banner_image_urls'] = array_map(
                    fn (string $path) => Storage::fileUrl($path),
                    $bannerImages,
                );
            }

            return $data;
        })->values()->all();
    }

    public function updateManager(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('updateManager', $ticket);

        $request->validate([
            'manager_email' => ['nullable', 'email', 'exists:users,email'],
        ]);

        $manager = $request->input('manager_email')
            ? User::where('email', $request->input('manager_email'))->firstOrFail()
            : null;

        $this->updateTicketAssignments->updateManager($ticket, $manager, $request->user()->id);

        return back();
    }

    public function updateUser(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('updateUser', $ticket);

        $request->validate([
            'user_email' => ['nullable', 'email', 'exists:users,email'],
        ]);

        $ticketUser = $request->input('user_email')
            ? User::where('email', $request->input('user_email'))->firstOrFail()
            : null;

        $this->updateTicketAssignments->updateUser($ticket, $ticketUser, $request->user()->id);

        return back();
    }
}
