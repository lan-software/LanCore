<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use OwenIt\Auditing\Models\Audit;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('allows admins to update personal data and writes an audit row', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create([
        'phone' => null,
        'street' => null,
        'city' => null,
        'zip_code' => null,
        'country' => null,
    ]);

    $this->actingAs($admin)
        ->patch("/users/{$user->id}/personal-data", [
            'phone' => '+49 123 4567',
            'street' => 'Hauptstrasse 1',
            'city' => 'Berlin',
            'zip_code' => '10115',
            'country' => 'DE',
            'profile_visibility' => 'logged_in',
            'is_ticket_discoverable' => true,
            'is_seat_visible_publicly' => false,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->fresh())
        ->phone->toBe('+49 123 4567')
        ->street->toBe('Hauptstrasse 1')
        ->city->toBe('Berlin')
        ->zip_code->toBe('10115')
        ->country->toBe('DE')
        ->profile_updated_at->not->toBeNull();

    $audit = Audit::query()
        ->where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->new_values)->toHaveKey('street', 'Hauptstrasse 1')
        ->and($audit->new_values)->toHaveKey('city', 'Berlin')
        ->and($audit->user_id)->toBe($admin->id);
});

it('rejects an invalid country code', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin)
        ->patch("/users/{$user->id}/personal-data", [
            'country' => 'GERMANY',
        ])
        ->assertSessionHasErrors('country');
});

it('forbids non-admins from updating personal data', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $target = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->patch("/users/{$target->id}/personal-data", [
            'phone' => '+49 123',
        ])
        ->assertForbidden();
});
