<?php

use App\Domain\EmailLog\Models\EmailMessage;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

it('writes a row to email_messages when MessageSent fires', function () {
    $email = (new Email)
        ->from(new Address('from@example.com', 'From'))
        ->to(new Address('to@example.com', 'To User'))
        ->subject('Hello there')
        ->html('<p>Hi</p>')
        ->text('Hi');

    $email->getHeaders()->addTextHeader('X-LanCore-Source', 'App\\Notifications\\TestNotification');
    $email->getHeaders()->addTextHeader('X-LanCore-Source-Label', 'Test notification');

    $envelope = new Envelope(
        new Address('from@example.com', 'From'),
        [new Address('to@example.com', 'To User')],
    );
    $sentMessage = new SentMessage(new SymfonySentMessage($email, $envelope));

    event(new MessageSent($sentMessage, ['mailer' => 'smtp']));

    $row = EmailMessage::query()->latest('id')->first();

    expect($row)->not->toBeNull()
        ->and($row->subject)->toBe('Hello there')
        ->and($row->from_address)->toBe('from@example.com')
        ->and($row->to_addresses[0]['address'])->toBe('to@example.com')
        ->and($row->html_body)->toBe('<p>Hi</p>')
        ->and($row->text_body)->toBe('Hi')
        ->and($row->mailer)->toBe('smtp')
        ->and($row->source)->toBe('App\\Notifications\\TestNotification')
        ->and($row->source_label)->toBe('Test notification')
        ->and($row->status->value)->toBe('sent')
        ->and($row->headers)->toHaveKey('X-LanCore-Source');
});

it('records emails without a custom source header (source is null)', function () {
    $email = (new Email)
        ->from(new Address('from@example.com'))
        ->to(new Address('plain@example.com'))
        ->subject('Plain mail')
        ->text('No source header');

    $envelope = new Envelope(
        new Address('from@example.com'),
        [new Address('plain@example.com')],
    );
    $sentMessage = new SentMessage(new SymfonySentMessage($email, $envelope));

    event(new MessageSent($sentMessage));

    $row = EmailMessage::query()->latest('id')->first();

    expect($row->source)->toBeNull()
        ->and($row->source_label)->toBeNull();
});
