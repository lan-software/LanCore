<?php

use App\Domain\Ticketing\Security\TicketKeyRing;

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir().'/lan-keys-rotate-'.bin2hex(random_bytes(4));
    mkdir($this->dir, 0700, true);
    config()->set('tickets.signing.keys_path', $this->dir);
    config()->set('tickets.pepper', 'test');
});

it('creates a new keyfile with 0600 permissions', function (): void {
    $this->artisan('tickets:keys:rotate')->assertSuccessful();

    $files = glob($this->dir.'/*.key');
    expect($files)->toHaveCount(1);

    $perms = fileperms($files[0]) & 0777;
    expect($perms)->toBe(0600);
});

it('retains previously rotated keys for verification', function (): void {
    $this->artisan('tickets:keys:rotate')->assertSuccessful();
    $this->artisan('tickets:keys:rotate')->assertSuccessful();

    $files = glob($this->dir.'/*.key');
    expect($files)->toHaveCount(2);

    $kids = array_map(fn ($f) => basename($f, '.key'), $files);
    config()->set('tickets.signing.active_kid', $kids[0]);
    config()->set('tickets.signing.verify_kids', $kids);
    app()->forgetInstance(TicketKeyRing::class);

    $ring = new TicketKeyRing;
    expect($ring->allVerifyKids())->toHaveCount(2);
});
