<?php

namespace App\Domain\News\Listeners;

use App\Domain\News\Events\NewsArticlePublished;
use App\Domain\Notification\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendNewsNotification implements ShouldQueue
{
    public function handle(NewsArticlePublished $event): void
    {
        $article = $event->newsArticle;

        $userIds = NotificationPreference::query()
            ->where('mail_on_news', true)
            ->pluck('user_id');

        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            Log::info('Sending news notification to user', [
                'user_id' => $user->id,
                'article_id' => $article->id,
                'article_title' => $article->title,
            ]);

            // TODO: Send actual mail notification (e.g. $user->notify(new NewsPublishedNotification($article)))
        }
    }
}
