<?php

namespace App\Domain\DataLifecycle\Listeners;

use App\Domain\DataLifecycle\Events\UserDeletionRequested;
use App\Domain\DataLifecycle\Mail\DeletionConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendDeletionConfirmationEmail implements ShouldQueue
{
    public function handle(UserDeletionRequested $event): void
    {
        $subject = $event->request->user;
        if ($subject === null || $subject->email === null) {
            return;
        }

        $confirmUrl = URL::route('data-lifecycle.account.confirm', ['token' => $event->plainToken]);
        $cancelUrl = URL::signedRoute(
            'data-lifecycle.account.cancel-via-link',
            ['request' => $event->request->getKey()],
            now()->addDays(35),
        );

        Mail::to($subject->email)->send(new DeletionConfirmationMail(
            subject: $subject,
            confirmUrl: $confirmUrl,
            cancelUrl: $cancelUrl,
        ));
    }
}
