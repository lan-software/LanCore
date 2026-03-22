<?php

namespace App\Domain\Webhook\Enums;

enum WebhookEvent: string
{
    case UserRegistered = 'user.registered';
    case AnnouncementPublished = 'announcement.published';
    case NewsArticlePublished = 'news_article.published';
    case EventPublished = 'event.published';

    public function label(): string
    {
        return match ($this) {
            self::UserRegistered => 'User Registered',
            self::AnnouncementPublished => 'Announcement Published',
            self::NewsArticlePublished => 'News Article Published',
            self::EventPublished => 'Event Published',
        };
    }
}
