<?php

namespace App\Domain\DataLifecycle\Listeners;

use App\Domain\DataLifecycle\Events\UserDeletionCancelled;
use App\Domain\DataLifecycle\Mail\DeletionCancelledMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendDeletionCancelledEmail implements ShouldQueue
{
    public function handle(UserDeletionCancelled $event): void
    {
        $subject = $event->request->user;
        if ($subject === null || $subject->email === null) {
            return;
        }

        Mail::to($subject->email)->send(new DeletionCancelledMail(user: $subject));
    }
}
