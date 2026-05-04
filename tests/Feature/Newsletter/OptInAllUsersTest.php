<?php

use App\Domain\Newsletter\Actions\OptInAllUsersToList;
use App\Domain\Newsletter\Jobs\SubscribeUserJob;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

it('queues one SubscribeUserJob per verified user', function (): void {
    Bus::fake();

    User::factory()->count(3)->create(['email_verified_at' => now()]);
    User::factory()->create(['email_verified_at' => null]);

    $list = NewsletterList::factory()->create();

    $count = app(OptInAllUsersToList::class)->execute($list);

    expect($count)->toBe(3);
    Bus::assertDispatchedTimes(SubscribeUserJob::class, 3);
});
