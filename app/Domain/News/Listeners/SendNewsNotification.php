<?php

namespace App\Domain\News\Listeners;

use App\Domain\News\Events\NewsArticlePublished;
use App\Models\User;
use App\Notifications\NewsPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendNewsNotification implements ShouldQueue
{
    public function handle(NewsArticlePublished $event): void
    {
        $users = User::all();

        Notification::send($users, new NewsPublishedNotification($event->newsArticle));
    }
}
