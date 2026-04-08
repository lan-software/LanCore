<?php

use App\Domain\Announcement\Enums\AnnouncementAudience;
use App\Domain\Announcement\Models\Announcement;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget('announcement.feed');
});

it('returns active satellite-visible announcements', function (): void {
    $visible = Announcement::factory()->published()->audience(AnnouncementAudience::Satellites)->create([
        'title' => 'Visible to satellites',
    ]);
    Announcement::factory()->published()->audience(AnnouncementAudience::All)->create([
        'title' => 'Visible to all',
    ]);
    Announcement::factory()->published()->audience(AnnouncementAudience::LancoreOnly)->create([
        'title' => 'Hidden from satellites',
    ]);
    Announcement::factory()->published()->audience(AnnouncementAudience::Internal)->create([
        'title' => 'Internal only',
    ]);

    $response = $this->getJson('/api/announcements/feed');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->all();

    expect($titles)->toContain('Visible to satellites', 'Visible to all')
        ->not->toContain('Hidden from satellites')
        ->not->toContain('Internal only');

    $first = collect($response->json('data'))->firstWhere('id', $visible->id);
    expect($first)->toHaveKeys(['id', 'audience', 'severity', 'title', 'body', 'starts_at', 'ends_at', 'dismissible']);
});

it('excludes unpublished and future announcements from the feed', function (): void {
    Announcement::factory()->audience(AnnouncementAudience::All)->create([
        'title' => 'Draft',
        'published_at' => null,
    ]);
    Announcement::factory()->audience(AnnouncementAudience::All)->create([
        'title' => 'Future',
        'published_at' => now()->addDay(),
    ]);

    $response = $this->getJson('/api/announcements/feed');

    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles)->not->toContain('Draft')->not->toContain('Future');
});

it('caches the public feed response', function (): void {
    Announcement::factory()->published()->audience(AnnouncementAudience::All)->create(['title' => 'First']);

    $this->getJson('/api/announcements/feed')->assertOk();

    expect(Cache::has('announcement.feed'))->toBeTrue();
});
