<?php

use App\Domain\Announcement\Enums\AnnouncementAudience;
use App\Domain\Announcement\Enums\AnnouncementPriority;
use App\Domain\Announcement\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Config;

it('removes the demo welcome announcement when APP_DEMO is disabled', function (): void {
    $this->artisan('db:seed-demo')->assertSuccessful();

    Announcement::query()->create([
        'title' => 'Welcome to the Lan-Software public demo!',
        'description' => 'stale demo announcement',
        'priority' => AnnouncementPriority::Normal,
        'audience' => AnnouncementAudience::All,
        'author_id' => User::query()->first()->id,
        'published_at' => now(),
    ]);

    Config::set('app.demo', false);

    $this->artisan('db:seed-demo')->assertSuccessful();

    expect(Announcement::query()
        ->where('title', 'Welcome to the Lan-Software public demo!')
        ->exists()
    )->toBeFalse();
});

it('seeds the demo welcome announcement when APP_DEMO is enabled', function (): void {
    Config::set('app.demo', true);

    $this->artisan('db:seed-demo')->assertSuccessful();

    expect(Announcement::query()
        ->where('title', 'Welcome to the Lan-Software public demo!')
        ->exists()
    )->toBeTrue();
});
