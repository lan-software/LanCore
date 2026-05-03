<?php

use App\Domain\EmailLog\Enums\EmailMessageStatus;
use App\Domain\EmailLog\Models\EmailMessage;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('lets admins view the email log index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    EmailMessage::create([
        'subject' => 'Hello world',
        'from_address' => 'from@example.com',
        'to_addresses' => [['address' => 'to@example.com', 'name' => null]],
        'status' => EmailMessageStatus::Sent,
        'sent_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin/emails')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/emails/Index')
                ->has('messages.data', 1)
                ->where('messages.data.0.subject', 'Hello world'),
        );
});

it('filters the index by status', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    EmailMessage::create([
        'subject' => 'sent mail',
        'to_addresses' => [['address' => 's@example.com', 'name' => null]],
        'status' => EmailMessageStatus::Sent,
    ]);
    $failed = EmailMessage::create([
        'subject' => 'failed mail',
        'to_addresses' => [['address' => 'f@example.com', 'name' => null]],
        'status' => EmailMessageStatus::Failed,
        'error' => 'SMTP refused',
    ]);

    $captured = [];
    $this->actingAs($admin)
        ->get('/admin/emails?status=failed')
        ->assertSuccessful()
        ->assertInertia(function ($page) use (&$captured) {
            $captured = collect($page->toArray()['props']['messages']['data'])
                ->pluck('id')
                ->all();

            return $page;
        });

    expect($captured)->toContain($failed->id)->toHaveCount(1);
});

it('lets admins view a single email', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $msg = EmailMessage::create([
        'subject' => 'Detail view',
        'to_addresses' => [['address' => 'd@example.com', 'name' => null]],
        'status' => EmailMessageStatus::Sent,
        'html_body' => '<p>body</p>',
    ]);

    $this->actingAs($admin)
        ->get("/admin/emails/{$msg->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/emails/Show')
                ->where('message.id', $msg->id)
                ->where('message.subject', 'Detail view')
                ->where('message.html_body', '<p>body</p>'),
        );
});

it('forbids non-admins from viewing the email log', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/admin/emails')
        ->assertForbidden();
});
