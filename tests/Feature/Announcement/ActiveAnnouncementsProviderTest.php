<?php

use App\Domain\Announcement\Enums\AnnouncementAudience;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Announcement\Services\ActiveAnnouncementsProvider;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('returns active lancore-facing announcements for a user', function (): void {
    $user = User::factory()->create();

    Announcement::factory()->published()->audience(AnnouncementAudience::LancoreOnly)->create(['title' => 'Lan only']);
    Announcement::factory()->published()->audience(AnnouncementAudience::All)->create(['title' => 'All']);
    Announcement::factory()->published()->audience(AnnouncementAudience::Satellites)->create(['title' => 'Satellites only']);

    $result = app(ActiveAnnouncementsProvider::class)->forCurrentUser($user);

    $titles = collect($result)->pluck('title')->all();
    expect($titles)->toContain('Lan only', 'All')->not->toContain('Satellites only');
});

it('filters announcements dismissed by the user', function (): void {
    $user = User::factory()->create();
    $dismissed = Announcement::factory()->published()->audience(AnnouncementAudience::All)->create(['title' => 'Dismissed']);
    Announcement::factory()->published()->audience(AnnouncementAudience::All)->create(['title' => 'Active']);

    $user->dismissedAnnouncements()->attach($dismissed->id);

    $titles = collect(app(ActiveAnnouncementsProvider::class)->forCurrentUser($user))->pluck('title')->all();

    expect($titles)->toContain('Active')->not->toContain('Dismissed');
});

it('handles null users without applying dismissal filter', function (): void {
    Announcement::factory()->published()->audience(AnnouncementAudience::All)->create(['title' => 'Anon']);

    $result = app(ActiveAnnouncementsProvider::class)->forCurrentUser(null);

    expect(collect($result)->pluck('title')->all())->toContain('Anon');
});

it('caches results per user', function (): void {
    $user = User::factory()->create();
    Announcement::factory()->published()->audience(AnnouncementAudience::All)->create();

    app(ActiveAnnouncementsProvider::class)->forCurrentUser($user);

    expect(Cache::has('announcement.active.'.$user->id))->toBeTrue();
});
