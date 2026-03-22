<?php

namespace App\Domain\News\Listeners;

use App\Domain\News\Events\NewsArticlePublished;
use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleNewsArticlePublishedWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(NewsArticlePublished $event): void
    {
        $article = $event->newsArticle;

        $this->dispatchWebhooks->execute(WebhookEvent::NewsArticlePublished, [
            'event' => WebhookEvent::NewsArticlePublished->value,
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'published_at' => $article->published_at?->toIso8601String(),
            ],
        ]);
    }
}
