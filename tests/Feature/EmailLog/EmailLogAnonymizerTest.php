<?php

use App\Domain\DataLifecycle\Anonymizers\EmailLogAnonymizer;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\EmailLog\Enums\EmailMessageStatus;
use App\Domain\EmailLog\Models\EmailMessage;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

it('scrubs email_messages addressed to an anonymized user', function () {
    $user = User::factory()->withRole(RoleName::User)->create([
        'email' => 'victim@example.com',
    ]);

    $matching = EmailMessage::create([
        'subject' => 'Your order #42 has shipped',
        'from_address' => 'shop@lancore.test',
        'to_addresses' => [['address' => 'victim@example.com', 'name' => 'Victim']],
        'cc_addresses' => [['address' => 'admin@example.com', 'name' => null]],
        'html_body' => '<p>Order details with PII</p>',
        'text_body' => 'Order details with PII',
        'headers' => ['X-LanCore-Source' => 'OrderShipped'],
        'status' => EmailMessageStatus::Sent,
    ]);

    $other = EmailMessage::create([
        'subject' => 'Newsletter',
        'from_address' => 'news@lancore.test',
        'to_addresses' => [['address' => 'somebody-else@example.com', 'name' => null]],
        'html_body' => '<p>Latest news</p>',
        'status' => EmailMessageStatus::Sent,
    ]);

    $result = (new EmailLogAnonymizer)->anonymize($user, AnonymizationMode::Anonymize);

    expect($result->recordsScrubbed)->toBe(1);

    $matching->refresh();
    expect($matching->subject)->toBe('[anonymized]')
        ->and($matching->html_body)->toBeNull()
        ->and($matching->text_body)->toBeNull()
        ->and($matching->headers)->toBeNull()
        ->and($matching->cc_addresses)->toBeNull()
        ->and($matching->to_addresses[0]['address'])->toBe('anonymized@deleted.local');

    // Other user's message is untouched.
    $other->refresh();
    expect($other->subject)->toBe('Newsletter')
        ->and($other->html_body)->toBe('<p>Latest news</p>');
});

it('purges email_messages on PurgeNow', function () {
    $user = User::factory()->withRole(RoleName::User)->create([
        'email' => 'purge@example.com',
    ]);

    EmailMessage::create([
        'subject' => 'Should be deleted',
        'to_addresses' => [['address' => 'purge@example.com', 'name' => null]],
        'status' => EmailMessageStatus::Sent,
    ]);

    $result = (new EmailLogAnonymizer)->anonymize($user, AnonymizationMode::PurgeNow);

    expect($result->recordsScrubbed)->toBe(1);
    expect(EmailMessage::query()->count())->toBe(0);
});
