<?php

use App\Domain\Chat\Enums\Permission as ChatPermission;
use App\Enums\RoleName;
use App\Enums\RolePermissionMap;

it('grants ModerateChat to Moderator', function (): void {
    $perms = RolePermissionMap::forRole(RoleName::Moderator);

    expect($perms)->toContain(ChatPermission::ModerateChat);
});

it('does not grant ManageChat to Moderator', function (): void {
    $perms = RolePermissionMap::forRole(RoleName::Moderator);

    expect($perms)->not->toContain(ChatPermission::ManageChat);
});

it('grants both ManageChat and ModerateChat to Admin', function (): void {
    $perms = RolePermissionMap::forRole(RoleName::Admin);

    expect($perms)
        ->toContain(ChatPermission::ManageChat)
        ->toContain(ChatPermission::ModerateChat);
});

it('grants both chat permissions to Superadmin via all()', function (): void {
    $all = RolePermissionMap::all();

    expect($all)
        ->toContain(ChatPermission::ManageChat)
        ->toContain(ChatPermission::ModerateChat);
});

it('grants no chat permissions to plain User', function (): void {
    $perms = RolePermissionMap::forRole(RoleName::User);

    expect($perms)
        ->not->toContain(ChatPermission::ManageChat)
        ->not->toContain(ChatPermission::ModerateChat);
});
