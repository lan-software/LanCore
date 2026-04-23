<?php

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use App\Domain\Ticketing\Jobs\GenerateTicketPdf;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Enums\RoleName;
use App\Models\OrganizationSetting;
use App\Models\Role;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    setUpTicketSigningKey('kid20260101a');
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

it('embeds the watermark image when one is provided to the Blade template', function (): void {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $ticket = Ticket::factory()->create(['owner_id' => $owner->id]);
    $ticket->rotateSignedToken(new TicketTokenService(new TicketKeyRing));

    $fakeWatermark = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUeJxjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';

    $html = view('pdf.ticket', [
        'ticket' => $ticket,
        'qrCode' => 'data:image/svg+xml;base64,PHN2Zy8+',
        'org' => OrganizationSetting::forInvoice(),
        'bannerBase64' => null,
        'watermarkBase64' => $fakeWatermark,
        'conditions' => GlobalPurchaseCondition::activeOrdered()->get(),
    ])->render();

    expect($html)->toContain('class="watermark"');
    expect($html)->toContain($fakeWatermark);
});

it('renders without watermark when GD helper returns null', function (): void {
    $ticket = Ticket::factory()->create();
    $ticket->rotateSignedToken(new TicketTokenService(new TicketKeyRing));

    $html = view('pdf.ticket', [
        'ticket' => $ticket,
        'qrCode' => 'data:image/svg+xml;base64,PHN2Zy8+',
        'org' => OrganizationSetting::forInvoice(),
        'bannerBase64' => null,
        'watermarkBase64' => null,
        'conditions' => GlobalPurchaseCondition::activeOrdered()->get(),
    ])->render();

    expect($html)->not->toContain('class="watermark"');
});

it('full GenerateTicketPdf run produces a non-empty PDF embedding a watermark', function (): void {
    Storage::fake('private');

    $owner = User::factory()->withRole(RoleName::User)->create(['name' => 'Forensic Tester']);
    $ticket = Ticket::factory()->create(['owner_id' => $owner->id]);
    $payload = $ticket->rotateSignedToken(new TicketTokenService(new TicketKeyRing));

    (new GenerateTicketPdf($ticket->id, $payload))->handle();

    $path = "tickets/{$ticket->id}.pdf";
    expect(StorageRole::private()->exists($path))->toBeTrue();
    expect(strlen(StorageRole::private()->get($path)))->toBeGreaterThan(10000);
});
