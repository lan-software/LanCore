<?php

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    setUpTicketSigningKey('kid20260101a');
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

it('serving the QR endpoint does not rotate the stored nonce hash or epoch', function (): void {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $ticket = Ticket::factory()->create(['owner_id' => $owner->id]);
    $ticket->rotateSignedToken(new TicketTokenService(new TicketKeyRing));
    $ticket->refresh();

    $hashBefore = $ticket->validation_nonce_hash;
    $epochBefore = $ticket->validation_rotation_epoch;

    $this->actingAs($owner)->get("/tickets/{$ticket->id}/qr")->assertSuccessful();
    $ticket->refresh();
    expect($ticket->validation_nonce_hash)->toBe($hashBefore);
    expect($ticket->validation_rotation_epoch)->toBe($epochBefore);

    $this->actingAs($owner)->get("/tickets/{$ticket->id}/qr")->assertSuccessful();
    $ticket->refresh();
    expect($ticket->validation_nonce_hash)->toBe($hashBefore);
    expect($ticket->validation_rotation_epoch)->toBe($epochBefore);
});
