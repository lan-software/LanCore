<?php

use App\Actions\User\UpdateUserAttributes;
use App\Domain\Notification\Events\UserAttributesUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('dispatches UserAttributesUpdated when attributes change', function () {
    Event::fake([UserAttributesUpdated::class]);

    $user = User::factory()->create(['name' => 'Original Name']);

    app(UpdateUserAttributes::class)->execute($user, ['name' => 'New Name']);

    Event::assertDispatched(UserAttributesUpdated::class, function (UserAttributesUpdated $event) use ($user) {
        return $event->user->is($user)
            && array_key_exists('name', $event->changedAttributes);
    });
});

it('does not dispatch UserAttributesUpdated when no attributes actually change', function () {
    Event::fake([UserAttributesUpdated::class]);

    $user = User::factory()->create(['name' => 'Same Name']);

    app(UpdateUserAttributes::class)->execute($user, ['name' => 'Same Name']);

    Event::assertNotDispatched(UserAttributesUpdated::class);
});
