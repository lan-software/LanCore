<?php

namespace App\Domain\News\Notifications;

use App\Domain\News\Models\NewsArticle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly NewsArticle $newsArticle) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel === 'database') {
            return true;
        }

        $preferences = $notifiable->notificationPreference;

        if (! $preferences) {
            return true;
        }

        return match ($channel) {
            'mail' => $preferences->mail_on_news,
            default => false,
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('news.notifications.published.subject', ['title' => $this->newsArticle->title]))
            ->line(__('news.notifications.published.body', ['title' => $this->newsArticle->title]))
            ->when($this->newsArticle->summary, fn (MailMessage $message) => $message->line($this->newsArticle->summary))
            ->action(__('news.notifications.published.action'), url('/news/'.$this->newsArticle->slug))
            ->line(__('news.notifications.published.preferences_hint'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'news_article_id' => $this->newsArticle->id,
            'title' => $this->newsArticle->title,
        ];
    }
}
