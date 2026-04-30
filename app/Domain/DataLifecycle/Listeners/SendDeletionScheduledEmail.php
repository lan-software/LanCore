<?php

namespace App\Domain\DataLifecycle\Listeners;

use App\Domain\DataLifecycle\Events\UserDeletionConfirmed;
use App\Domain\DataLifecycle\Mail\DeletionScheduledMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendDeletionScheduledEmail implements ShouldQueue
{
    public function handle(UserDeletionConfirmed $event): void
    {
        $subject = $event->request->user;
        if ($subject === null || $subject->email === null || $event->request->scheduled_for === null) {
            return;
        }

        $cancelUrl = URL::signedRoute(
            'data-lifecycle.account.cancel-via-link',
            ['request' => $event->request->getKey()],
            $event->request->scheduled_for,
        );

        Mail::to($subject->email)->send(new DeletionScheduledMail(
            subject: $subject,
            scheduledFor: $event->request->scheduled_for,
            cancelUrl: $cancelUrl,
        ));
    }
}
