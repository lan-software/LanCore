<?php

namespace App\Enums;

enum Permission: string
{
    // Content & Community
    case ManageAchievements = 'manage_achievements';
    case ManageAnnouncements = 'manage_announcements';
    case ManageNewsArticles = 'manage_news_articles';
    case ModerateNewsComments = 'moderate_news_comments';

    // Event & Program
    case ManageEvents = 'manage_events';
    case ManagePrograms = 'manage_programs';
    case ManageGames = 'manage_games';

    // Venue & Seating
    case ManageVenues = 'manage_venues';
    case ManageSeatPlans = 'manage_seat_plans';

    // Ticketing
    case ManageTicketing = 'manage_ticketing';
    case CheckInTickets = 'check_in_tickets';

    // Shop & Finance
    case ViewOrders = 'view_orders';
    case ManageOrders = 'manage_orders';
    case ManageVouchers = 'manage_vouchers';
    case ManageShopConditions = 'manage_shop_conditions';

    // Sponsoring
    case ManageSponsors = 'manage_sponsors';
    case ManageSponsorLevels = 'manage_sponsor_levels';
    case ManageAssignedSponsors = 'manage_assigned_sponsors';

    // System
    case ManageIntegrations = 'manage_integrations';
    case ManageWebhooks = 'manage_webhooks';
    case ManageUsers = 'manage_users';
    case SyncUserRoles = 'sync_user_roles';
    case DeleteUsers = 'delete_users';

    // Audit
    case ViewAuditLogs = 'view_audit_logs';

    /**
     * Return the static set of permissions for a given role.
     *
     * @return array<int, self>
     */
    public static function forRole(RoleName $role): array
    {
        return match ($role) {
            RoleName::User => [],

            RoleName::Moderator => [
                self::ModerateNewsComments,
                self::ManageAnnouncements,
            ],

            RoleName::SponsorManager => [
                self::ManageAssignedSponsors,
            ],

            RoleName::Admin => [
                self::ManageAchievements,
                self::ManageAnnouncements,
                self::ManageNewsArticles,
                self::ModerateNewsComments,
                self::ManageEvents,
                self::ManagePrograms,
                self::ManageGames,
                self::ManageVenues,
                self::ManageSeatPlans,
                self::ManageTicketing,
                self::CheckInTickets,
                self::ViewOrders,
                self::ManageOrders,
                self::ManageVouchers,
                self::ManageShopConditions,
                self::ManageSponsors,
                self::ManageSponsorLevels,
                self::ManageIntegrations,
                self::ManageWebhooks,
                self::ManageUsers,
                self::ViewAuditLogs,
            ],

            RoleName::Superadmin => self::cases(),
        };
    }
}
