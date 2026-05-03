<?php

use App\Domain\Auth\Steam\Enums\SteamLinkStatus;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('filters users by linked Steam status', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $linked = User::factory()->withRole(RoleName::User)->create([
        'steam_id_64' => '76561198000000001',
        'password' => bcrypt('secret'),
    ]);
    $steamOnly = User::factory()->withRole(RoleName::User)->create([
        'steam_id_64' => '76561198000000002',
        'password' => null,
    ]);
    $notLinked = User::factory()->withRole(RoleName::User)->create([
        'steam_id_64' => null,
        'password' => bcrypt('secret'),
    ]);

    $idsFor = function (string $status) use ($admin): array {
        $captured = [];

        $this->actingAs($admin)
            ->get('/users?steam_status='.$status.'&per_page=100')
            ->assertSuccessful()
            ->assertInertia(function ($page) use (&$captured) {
                $captured = collect($page->toArray()['props']['users']['data'])
                    ->pluck('id')
                    ->all();

                return $page;
            });

        return $captured;
    };

    $linkedIds = $idsFor(SteamLinkStatus::Linked->value);
    expect($linkedIds)
        ->toContain($linked->id)
        ->not->toContain($steamOnly->id)
        ->not->toContain($notLinked->id);

    $steamOnlyIds = $idsFor(SteamLinkStatus::SteamOnly->value);
    expect($steamOnlyIds)
        ->toContain($steamOnly->id)
        ->not->toContain($linked->id)
        ->not->toContain($notLinked->id);

    $notLinkedIds = $idsFor(SteamLinkStatus::NotLinked->value);
    expect($notLinkedIds)
        ->toContain($notLinked->id)
        ->not->toContain($linked->id)
        ->not->toContain($steamOnly->id);
});

it('rejects an invalid steam_status value', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/users?steam_status=bogus')
        ->assertSessionHasErrors('steam_status');
});

it('exposes computed steam_status on each row in the index payload', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    User::factory()->withRole(RoleName::User)->create([
        'steam_id_64' => '76561198000000003',
        'password' => null,
    ]);

    $this->actingAs($admin)
        ->get('/users')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Index')
                ->has('users.data')
                ->where('users.data.0.steam_status', fn ($value) => in_array(
                    $value,
                    ['linked', 'steam_only', 'not_linked'],
                    true,
                )),
        );
});
