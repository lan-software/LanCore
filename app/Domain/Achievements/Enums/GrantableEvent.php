<?php

namespace App\Domain\Achievements\Enums;

enum GrantableEvent: string
{
    case AnnouncementPublished = 'App\\Domain\\Announcement\\Events\\AnnouncementPublished';
    case AnnouncementsViewed = 'App\\Domain\\Announcement\\Events\\AnnouncementsViewed';
    case CartItemAdded = 'App\\Domain\\Shop\\Events\\CartItemAdded';
    case EventPublished = 'App\\Domain\\Event\\Events\\EventPublished';
    case IntegrationAccessed = 'App\\Domain\\Integration\\Events\\IntegrationAccessed';
    case NewsArticlePublished = 'App\\Domain\\News\\Events\\NewsArticlePublished';
    case NewsArticleRead = 'App\\Domain\\News\\Events\\NewsArticleRead';
    case NotificationPreferencesUpdated = 'App\\Domain\\Notification\\Events\\NotificationPreferencesUpdated';
    case NotificationsArchived = 'App\\Domain\\Notification\\Events\\NotificationsArchived';
    case ProfileUpdated = 'App\\Domain\\Notification\\Events\\ProfileUpdated';
    case TicketDiscoverySettingsUpdated = 'App\\Domain\\Notification\\Events\\TicketDiscoverySettingsUpdated';
    case TicketPurchased = 'App\\Domain\\Shop\\Events\\TicketPurchased';
    case UserRegistered = 'Illuminate\\Auth\\Events\\Registered';
    case UserRolesChanged = 'App\\Domain\\Notification\\Events\\UserRolesChanged';

    public function label(): string
    {
        return match ($this) {
            self::AnnouncementPublished => 'Announcement Published',
            self::AnnouncementsViewed => 'Announcements Viewed',
            self::CartItemAdded => 'Cart Item Added',
            self::EventPublished => 'Event Published',
            self::IntegrationAccessed => 'Integration Accessed',
            self::NewsArticlePublished => 'News Article Published',
            self::NewsArticleRead => 'News Article Read',
            self::NotificationPreferencesUpdated => 'Notification Preferences Updated',
            self::NotificationsArchived => 'Notifications Archived',
            self::ProfileUpdated => 'Profile Updated',
            self::TicketDiscoverySettingsUpdated => 'Ticket Discovery Settings Updated',
            self::TicketPurchased => 'Ticket Purchased',
            self::UserRegistered => 'User Registered',
            self::UserRolesChanged => 'User Roles Changed',
        };
    }
}
