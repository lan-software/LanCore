<?php

use App\Domain\Ticketing\Actions\UpdateTicketAssignments;
use App\Domain\Ticketing\Jobs\GenerateTicketPdf;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Notifications\TicketTokenRotatedNotification;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    setUpTicketSigningKey('kid20260101a');
    Queue::fake();
    Notification::fake();

    $this->action = new UpdateTicketAssignments(new TicketTokenService(new TicketKeyRing));
    $this->ticket = Ticket::factory()->create();
    $this->ticket->rotateSignedToken(new TicketTokenService(new TicketKeyRing));
    $this->ticket->refresh();
});

it('rotates nonce_hash and dispatches PDF on addUser', function (): void {
    $previousHash = $this->ticket->validation_nonce_hash;
    $owner = $this->ticket->owner;
    $user = User::factory()->create();

    $this->action->addUser($this->ticket, $user, performedBy: 1);
    $this->ticket->refresh();

    expect($this->ticket->validation_nonce_hash)->not->toBeNull();
    expect($this->ticket->validation_nonce_hash)->not->toBe($previousHash);

    Queue::assertPushed(GenerateTicketPdf::class);
    Notification::assertSentTo($user, TicketTokenRotatedNotification::class);
    if ($owner) {
        Notification::assertSentTo($owner, TicketTokenRotatedNotification::class);
    }
});

it('notifies the removed user on removeUser', function (): void {
    $user = User::factory()->create();
    $this->action->addUser($this->ticket, $user, performedBy: 1);
    Notification::fake();

    $this->action->removeUser($this->ticket->fresh(), $user, performedBy: 1);

    Notification::assertSentTo($user, TicketTokenRotatedNotification::class);
});

it('notifies the previous manager on updateManager', function (): void {
    $previousManager = User::factory()->create();
    $this->ticket->update(['manager_id' => $previousManager->id]);
    $this->ticket->refresh();

    $newManager = User::factory()->create();
    Notification::fake();

    $this->action->updateManager($this->ticket, $newManager, performedBy: 1);

    Notification::assertSentTo($previousManager, TicketTokenRotatedNotification::class);
});

it('invalidates the prior nonce so the old token cannot locate the ticket', function (): void {
    $service = new TicketTokenService(new TicketKeyRing);
    $oldPayload = $this->ticket->rotateSignedToken($service);
    $oldVerification = $service->verify($oldPayload);

    $this->action->rotateToken($this->ticket);

    expect($service->locate($oldVerification))->toBeNull();
});
