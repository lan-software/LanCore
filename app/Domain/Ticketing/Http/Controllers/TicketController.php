<?php

namespace App\Domain\Ticketing\Http\Controllers;

use App\Domain\Ticketing\Actions\UpdateTicketAssignments;
use App\Domain\Ticketing\Jobs\GenerateTicketPdf;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StorageRole;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-005, CAP-TKT-006
 * @see docs/mil-std-498/SRS.md TKT-F-004, TKT-F-005, TKT-F-006
 */
class TicketController extends Controller
{
    public function __construct(
        private readonly UpdateTicketAssignments $updateTicketAssignments,
        private readonly TicketTokenService $tokenService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $selectedEventId = $request->session()->get('my_selected_event_id');

        $ownedTickets = $user->ownedTickets()
            ->when($selectedEventId, fn ($q) => $q->where('event_id', $selectedEventId))
            ->with(['ticketType', 'event', 'manager', 'users', 'addons', 'order'])
            ->get();

        $managedTickets = $user->managedTickets()
            ->where('owner_id', '!=', $user->id)
            ->when($selectedEventId, fn ($q) => $q->where('event_id', $selectedEventId))
            ->with(['ticketType', 'event', 'owner', 'users', 'addons', 'order'])
            ->get();

        $assignedTickets = $user->assignedTickets()
            ->where('owner_id', '!=', $user->id)
            ->where(function ($query) use ($user) {
                $query->where('manager_id', '!=', $user->id)
                    ->orWhereNull('manager_id');
            })
            ->when($selectedEventId, fn ($q) => $q->where('tickets.event_id', $selectedEventId))
            ->with(['ticketType', 'event', 'owner', 'users', 'addons', 'order'])
            ->get();

        return Inertia::render('tickets/Index', [
            'ownedTickets' => $this->enrichTickets($ownedTickets),
            'managedTickets' => $this->enrichTickets($managedTickets),
            'assignedTickets' => $this->enrichTickets($assignedTickets),
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
            'users',
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
                    fn (string $path) => StorageRole::publicUrl($path),
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

    public function addUser(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('updateUser', $ticket);

        $request->validate([
            'user_email' => ['required', 'email', 'exists:users,email'],
        ]);

        $ticketUser = User::where('email', $request->input('user_email'))->firstOrFail();

        $this->updateTicketAssignments->addUser($ticket, $ticketUser, $request->user()->id);

        return back();
    }

    public function removeUser(Request $request, Ticket $ticket, User $user): RedirectResponse
    {
        $this->authorize('updateUser', $ticket);

        $this->updateTicketAssignments->removeUser($ticket, $user, $request->user()->id);

        return back();
    }

    public function download(Ticket $ticket): StreamedResponse
    {
        $this->authorize('view', $ticket);

        $path = "tickets/{$ticket->id}.pdf";

        if (! StorageRole::private()->exists($path)) {
            $payload = $ticket->issueSignedToken($this->tokenService);
            $job = new GenerateTicketPdf($ticket->id, $payload);
            $job->handle();
        }

        return StorageRole::private()->download($path, "ticket-{$ticket->id}.pdf");
    }

    public function qrCode(Ticket $ticket): HttpResponse
    {
        $this->authorize('view', $ticket);

        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd,
        );

        $payload = $ticket->issueSignedToken($this->tokenService);
        $writer = new Writer($renderer);
        $svg = $writer->writeString($payload);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
