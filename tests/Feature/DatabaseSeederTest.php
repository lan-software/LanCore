<?php

use App\Domain\Event\Models\Event;
use App\Domain\Venue\Models\Venue;
use App\Models\Role;
use App\Models\User;

it('only seeds roles and no test data', function () {
    $this->artisan('db:seed')
        ->assertSuccessful();

    expect(Role::count())->toBe(4)
        ->and(User::count())->toBe(0)
        ->and(Event::count())->toBe(0)
        ->and(Venue::count())->toBe(0);
});
