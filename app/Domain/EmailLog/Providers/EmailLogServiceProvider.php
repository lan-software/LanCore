<?php

namespace App\Domain\EmailLog\Providers;

use App\Domain\EmailLog\Listeners\RecordSentEmail;
use App\Domain\EmailLog\Models\EmailMessage;
use App\Domain\EmailLog\Policies\EmailMessagePolicy;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class EmailLogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(MessageSent::class, RecordSentEmail::class);

        Gate::policy(EmailMessage::class, EmailMessagePolicy::class);
    }
}
