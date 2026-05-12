<?php

use App\Domain\Achievements\Contracts\HasGrantingUser;
use App\Domain\Achievements\Listeners\ProcessAchievements;
use App\Domain\Achievements\Support\GrantableEventRegistry;
use App\Domain\News\Events\NewsArticleRead;
use App\Domain\Notification\Events\NotificationsArchived;
use App\Domain\Notification\Events\ProfileUpdated;
use App\Domain\Shop\Events\TicketPurchased;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;

it('discovers all events that expose a User instance', function () {
    $registry = new GrantableEventRegistry;
    $registry->flush();

    $classes = $registry->eventClasses();

    expect($classes)
        ->toContain(Registered::class)
        ->toContain(NewsArticleRead::class)
        ->toContain(TicketPurchased::class)
        ->toContain(NotificationsArchived::class)
        ->toContain(ProfileUpdated::class);
});

it('omits events without a user property/method/marker', function () {
    $registry = new GrantableEventRegistry;
    $registry->flush();

    $classes = $registry->eventClasses();

    // AnnouncementPublished carries an Announcement, not a User.
    expect($classes)->not->toContain('App\\Domain\\Announcement\\Events\\AnnouncementPublished');
    // EventPublished carries an Event, not a User.
    expect($classes)->not->toContain('App\\Domain\\Event\\Events\\EventPublished');
});

it('honours the HasGrantingUser marker interface', function () {
    $event = new class implements HasGrantingUser
    {
        public function __construct(public readonly ?User $resolved = null) {}

        public function grantingUser(): ?User
        {
            return $this->resolved;
        }
    };

    $user = User::factory()->create();
    $populated = new class($user) implements HasGrantingUser
    {
        public function __construct(public readonly ?User $resolved) {}

        public function grantingUser(): ?User
        {
            return $this->resolved;
        }
    };

    $registry = new GrantableEventRegistry;
    expect($registry->userFor($event))->toBeNull();
    expect($registry->userFor($populated)?->id)->toBe($user->id);
});

it('produces humanized labels for FQCNs', function () {
    $registry = new GrantableEventRegistry;

    expect($registry->labelFor('App\\Domain\\News\\Events\\NewsArticleRead'))
        ->toBe('News Article Read');
});

it('the ProcessAchievements listener remains queued', function () {
    expect(is_subclass_of(ProcessAchievements::class, ShouldQueue::class))->toBeTrue();
});
