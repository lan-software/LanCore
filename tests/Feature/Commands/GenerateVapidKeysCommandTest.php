<?php

use Illuminate\Support\Facades\File;

it('writes VAPID keys to the .env file', function () {
    $envPath = app()->environmentFilePath();
    $originalContents = File::get($envPath);

    // Ensure blank keys to start
    $contents = preg_replace('/^VAPID_PUBLIC_KEY=.*/m', 'VAPID_PUBLIC_KEY=', $originalContents);
    $contents = preg_replace('/^VAPID_PRIVATE_KEY=.*/m', 'VAPID_PRIVATE_KEY=', $contents);
    File::put($envPath, $contents);

    config(['webpush.vapid.public_key' => '', 'webpush.vapid.private_key' => '']);

    $this->artisan('webpush:vapid')
        ->expectsOutputToContain('VAPID keys set successfully.')
        ->assertSuccessful();

    $envAfter = File::get($envPath);

    expect($envAfter)->toMatch('/^VAPID_PUBLIC_KEY=.{20,}/m');
    expect($envAfter)->toMatch('/^VAPID_PRIVATE_KEY=.{20,}/m');

    // Restore original .env
    File::put($envPath, $originalContents);
});

it('displays keys with --show without modifying .env', function () {
    $envPath = app()->environmentFilePath();
    $originalContents = File::get($envPath);

    $this->artisan('webpush:vapid', ['--show' => true])
        ->expectsOutputToContain('VAPID_PUBLIC_KEY=')
        ->expectsOutputToContain('VAPID_PRIVATE_KEY=')
        ->assertSuccessful();

    expect(File::get($envPath))->toBe($originalContents);
});
