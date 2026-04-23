<?php

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Enums\RoleName;
use App\Models\OrganizationSetting;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    setUpTicketSigningKey('kid20260101a');
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

it('renders the ticket blade with conditions and fallback banner', function (): void {
    GlobalPurchaseCondition::factory()->create([
        'name' => 'Rule Alpha',
        'content' => 'Alpha text body.',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    GlobalPurchaseCondition::factory()->create([
        'name' => 'Rule Beta',
        'content' => 'Beta text body.',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $owner = User::factory()->withRole(RoleName::User)->create();
    $ticket = Ticket::factory()->create(['owner_id' => $owner->id, 'manager_id' => $owner->id]);
    $ticket->rotateSignedToken(new TicketTokenService(new TicketKeyRing));
    $ticket->load(['ticketType', 'event', 'owner', 'manager', 'users', 'addons', 'order']);

    $html = view('pdf.ticket', [
        'ticket' => $ticket,
        'qrCode' => 'data:image/svg+xml;base64,PHN2Zy8+',
        'org' => OrganizationSetting::forInvoice(),
        'bannerBase64' => null,
        'watermarkBase64' => null,
        'conditions' => GlobalPurchaseCondition::activeOrdered()->get(),
    ])->render();

    expect($html)->toContain('Rule Alpha');
    expect($html)->toContain('Rule Beta');
    expect($html)->toContain('Alpha text body.');
    expect($html)->toContain('fold here');
    expect($html)->toContain("Ticket #{$ticket->id}");
});
