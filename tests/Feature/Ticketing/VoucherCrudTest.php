<?php

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\VoucherType;
use App\Domain\Shop\Models\Voucher;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view vouchers index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Voucher::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/vouchers')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('vouchers/Index')
                ->has('vouchers.data', 3)
        );
});

it('allows admins to store a percentage voucher', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/vouchers', [
            'code' => 'SUMMER10',
            'type' => VoucherType::Percentage->value,
            'discount_percent' => 10,
            'is_active' => true,
        ])
        ->assertRedirect('/vouchers');

    expect(Voucher::where('code', 'SUMMER10')->exists())->toBeTrue();
});

it('allows admins to store a fixed amount voucher', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/vouchers', [
            'code' => 'FLAT500',
            'type' => VoucherType::FixedAmount->value,
            'discount_amount' => 500,
            'max_uses' => 100,
            'is_active' => true,
            'event_id' => $event->id,
        ])
        ->assertRedirect('/vouchers');

    $voucher = Voucher::where('code', 'FLAT500')->first();
    expect($voucher)
        ->type->toBe(VoucherType::FixedAmount)
        ->discount_amount->toBe(500)
        ->max_uses->toBe(100);
});

it('validates unique voucher code', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Voucher::factory()->create(['code' => 'TAKEN']);

    $this->actingAs($admin)
        ->post('/vouchers', [
            'code' => 'TAKEN',
            'type' => VoucherType::Percentage->value,
            'discount_percent' => 5,
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['code']);
});

it('allows admins to update a voucher', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $voucher = Voucher::factory()->create();

    $this->actingAs($admin)
        ->patch("/vouchers/{$voucher->id}", [
            'code' => 'UPDATEDCODE',
            'type' => $voucher->type->value,
            'discount_percent' => $voucher->type === VoucherType::Percentage ? 15 : null,
            'discount_amount' => $voucher->type === VoucherType::FixedAmount ? $voucher->discount_amount : null,
            'is_active' => false,
        ])
        ->assertRedirect();

    $updated = $voucher->fresh();
    expect($updated->code)->toBe('UPDATEDCODE');
    expect($updated->is_active)->toBeFalse();
});

it('allows admins to delete a voucher', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $voucher = Voucher::factory()->create();

    $this->actingAs($admin)
        ->delete("/vouchers/{$voucher->id}")
        ->assertRedirect('/vouchers');

    expect(Voucher::find($voucher->id))->toBeNull();
});

it('denies regular users access to vouchers', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/vouchers')
        ->assertForbidden();
});
