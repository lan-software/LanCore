<?php

use App\Domain\DataLifecycle\Actions\DeleteEvent;
use App\Domain\DataLifecycle\Actions\RestoreEvent;
use App\Domain\Event\Models\Event;
use App\Domain\Event\Policies\EventPolicy;
use App\Models\User;

it('soft-deletes an event so it no longer appears in default queries', function () {
    $event = Event::factory()->create();

    app(DeleteEvent::class)->execute($event);

    expect(Event::query()->find($event->id))->toBeNull();
    expect(Event::withTrashed()->find($event->id))->not->toBeNull();
});

it('can restore a soft-deleted event', function () {
    $event = Event::factory()->create();

    app(DeleteEvent::class)->execute($event);
    app(RestoreEvent::class)->execute($event->fresh());

    expect(Event::query()->find($event->id))->not->toBeNull();
});

it('forbids hard-deletion via EventPolicy::forceDelete', function () {
    $admin = User::factory()->create();
    $event = Event::factory()->create();

    expect((new EventPolicy)->forceDelete($admin, $event))->toBeFalse();
});
