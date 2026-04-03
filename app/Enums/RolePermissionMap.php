<?php

namespace App\Enums;

use App\Contracts\PermissionEnum;
use App\Domain\Achievements\Enums\Permission as AchievementPermission;
use App\Domain\Announcement\Enums\Permission as AnnouncementPermission;
use App\Domain\Event\Enums\Permission as EventPermission;
use App\Domain\Games\Enums\Permission as GamePermission;
use App\Domain\Integration\Enums\Permission as IntegrationPermission;
use App\Domain\News\Enums\Permission as NewsPermission;
use App\Domain\Program\Enums\Permission as ProgramPermission;
use App\Domain\Seating\Enums\Permission as SeatingPermission;
use App\Domain\Shop\Enums\Permission as ShopPermission;
use App\Domain\Sponsoring\Enums\Permission as SponsoringPermission;
use App\Domain\Ticketing\Enums\Permission as TicketingPermission;
use App\Domain\Venue\Enums\Permission as VenuePermission;
use App\Domain\Webhook\Enums\Permission as WebhookPermission;

final class RolePermissionMap
{
    /**
     * Return the static set of permissions for a given role.
     *
     * @return array<int, PermissionEnum>
     */
    public static function forRole(RoleName $role): array
    {
        return match ($role) {
            RoleName::User => [],

            RoleName::Moderator => [
                NewsPermission::ModerateNewsComments,
                AnnouncementPermission::ManageAnnouncements,
            ],

            RoleName::SponsorManager => [
                SponsoringPermission::ManageAssignedSponsors,
            ],

            RoleName::Admin => [
                AchievementPermission::ManageAchievements,
                AnnouncementPermission::ManageAnnouncements,
                NewsPermission::ManageNewsArticles,
                NewsPermission::ModerateNewsComments,
                EventPermission::ManageEvents,
                ProgramPermission::ManagePrograms,
                GamePermission::ManageGames,
                VenuePermission::ManageVenues,
                SeatingPermission::ManageSeatPlans,
                TicketingPermission::ManageTicketing,
                TicketingPermission::CheckInTickets,
                ShopPermission::ViewOrders,
                ShopPermission::ManageOrders,
                ShopPermission::ManageVouchers,
                ShopPermission::ManageShopConditions,
                SponsoringPermission::ManageSponsors,
                SponsoringPermission::ManageSponsorLevels,
                IntegrationPermission::ManageIntegrations,
                WebhookPermission::ManageWebhooks,
                Permission::ManageUsers,
                Permission::ViewAuditLogs,
            ],

            RoleName::Superadmin => self::all(),
        };
    }

    /**
     * Collect every permission case from all domain enums.
     *
     * @return array<int, PermissionEnum>
     */
    public static function all(): array
    {
        return [
            ...Permission::cases(),
            ...AchievementPermission::cases(),
            ...AnnouncementPermission::cases(),
            ...EventPermission::cases(),
            ...GamePermission::cases(),
            ...IntegrationPermission::cases(),
            ...NewsPermission::cases(),
            ...ProgramPermission::cases(),
            ...SeatingPermission::cases(),
            ...ShopPermission::cases(),
            ...SponsoringPermission::cases(),
            ...TicketingPermission::cases(),
            ...VenuePermission::cases(),
            ...WebhookPermission::cases(),
        ];
    }
}
