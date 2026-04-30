<?php

use App\Domain\DataLifecycle\Actions\PurgeExpiredData;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Domain\Shop\Models\Order;
use App\Models\User;
use Database\Seeders\RetentionPolicySeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RetentionPolicySeeder::class);
});

it('does not purge users whose retention has not expired', function () {
    $user = User::factory()->create();
    Order::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

    // Simulate the user being already anonymized + soft-deleted.
    DB::table('users')->where('id', $user->id)->update([
        'deleted_at' => now(),
        'anonymized_at' => now(),
    ]);

    $stats = app(PurgeExpiredData::class)->execute();

    expect($stats['users_purged'])->toBe(0);
});

it('purges users whose retention has expired and policy allows force-delete', function () {
    RetentionPolicy::query()
        ->where('data_class', RetentionDataClass::ShopOrder->value)
        ->update(['retention_days' => 0]);

    $user = User::factory()->create();

    DB::table('users')->where('id', $user->id)->update([
        'deleted_at' => now()->subYears(2),
        'anonymized_at' => now()->subYears(2),
    ]);

    $stats = app(PurgeExpiredData::class)->execute();

    expect($stats['users_purged'])->toBe(1);
    expect(User::withTrashed()->find($user->id))->toBeNull();
});
