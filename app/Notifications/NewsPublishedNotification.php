<?php

namespace App\Notifications;

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
            ->subject('New article: '.$this->newsArticle->title)
            ->line('A new article has been published: '.$this->newsArticle->title)
            ->when($this->newsArticle->summary, fn (MailMessage $message) => $message->line($this->newsArticle->summary))
            ->action('Read article', url('/news/'.$this->newsArticle->slug))
            ->line('You can manage your notification preferences in your account settings.');
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
