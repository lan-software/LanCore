<?php

namespace App\Domain\Webhook\Enums;

/**
 * @see docs/mil-std-498/SSS.md CAP-WHK-004
 * @see docs/mil-std-498/SRS.md WHK-F-002
 */
enum WebhookEvent: string
{
    case UserRegistered = 'user.registered';
    case AnnouncementPublished = 'announcement.published';
    case NewsArticlePublished = 'news_article.published';
    case EventPublished = 'event.published';
    case TicketPurchased = 'ticket.purchased';
    case ProfileUpdated = 'user.profile_updated';
    case UserRolesUpdated = 'user.roles_updated';
    case IntegrationAccessed = 'integration.accessed';

    public function label(): string
    {
        return match ($this) {
            self::UserRegistered => 'User Registered',
            self::AnnouncementPublished => 'Announcement Published',
            self::NewsArticlePublished => 'News Article Published',
            self::EventPublished => 'Event Published',
            self::TicketPurchased => 'Ticket Purchased',
            self::ProfileUpdated => 'Profile Updated',
            self::UserRolesUpdated => 'User Roles Updated',
            self::IntegrationAccessed => 'Integration Accessed',
        };
    }
}
