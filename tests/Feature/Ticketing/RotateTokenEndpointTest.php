<?php

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Notifications\TicketTokenRotatedNotification;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    setUpTicketSigningKey('kid20260101a');
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Queue::fake();
    Notification::fake();

    $this->owner = User::factory()->withRole(RoleName::User)->create();
    $this->ticket = Ticket::factory()->create(['owner_id' => $this->owner->id, 'manager_id' => $this->owner->id]);
    $this->ticket->rotateSignedToken(new TicketTokenService(new TicketKeyRing));
    $this->ticket->refresh();
});

it('lets the owner rotate the token', function (): void {
    $hashBefore = $this->ticket->validation_nonce_hash;
    $epochBefore = $this->ticket->validation_rotation_epoch;

    $this->actingAs($this->owner)
        ->post("/tickets/{$this->ticket->id}/rotate-token")
        ->assertRedirect();

    $this->ticket->refresh();
    expect($this->ticket->validation_nonce_hash)->not->toBe($hashBefore);
    expect($this->ticket->validation_rotation_epoch)->toBe($epochBefore + 1);

    Notification::assertSentTo($this->owner, TicketTokenRotatedNotification::class);
});

it('lets the manager rotate the token', function (): void {
    $manager = User::factory()->withRole(RoleName::User)->create();
    $this->ticket->update(['manager_id' => $manager->id]);

    $this->actingAs($manager)
        ->post("/tickets/{$this->ticket->id}/rotate-token")
        ->assertRedirect();

    $this->ticket->refresh();
    expect($this->ticket->validation_rotation_epoch)->toBeGreaterThan(1);
});

it('denies rotation for an assigned-only user', function (): void {
    $stranger = User::factory()->withRole(RoleName::User)->create();
    $this->ticket->users()->attach($stranger->id);

    $this->actingAs($stranger)
        ->post("/tickets/{$this->ticket->id}/rotate-token")
        ->assertForbidden();
});

it('redirects guests to login', function (): void {
    $this->post("/tickets/{$this->ticket->id}/rotate-token")
        ->assertRedirect('/login');
});
