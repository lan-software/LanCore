// @see docs/mil-std-498/SRS.md USR-F-019

export const Permission = {
    // User Management (app/Enums/Permission.php)
    ManageUsers: 'manage_users',
    SyncUserRoles: 'sync_user_roles',
    DeleteUsers: 'delete_users',
    ExportUserPersonalData: 'export_user_personal_data',

    // Audit (app/Enums/AuditPermission.php)
    ViewAuditLogs: 'view_audit_logs',

    // Achievements (app/Domain/Achievements/Enums/Permission.php)
    ManageAchievements: 'manage_achievements',

    // Announcements (app/Domain/Announcement/Enums/Permission.php)
    ManageAnnouncements: 'manage_announcements',

    // Events (app/Domain/Event/Enums/Permission.php)
    ManageEvents: 'manage_events',

    // Competitions (app/Domain/Competition/Enums/Permission.php)
    ManageCompetitions: 'manage_competitions',

    // Games (app/Domain/Games/Enums/Permission.php)
    ManageGames: 'manage_games',

    // Integration (app/Domain/Integration/Enums/Permission.php)
    ManageIntegrations: 'manage_integrations',

    // News (app/Domain/News/Enums/Permission.php)
    ManageNewsArticles: 'manage_news_articles',
    ModerateNewsComments: 'moderate_news_comments',

    // Programs (app/Domain/Program/Enums/Permission.php)
    ManagePrograms: 'manage_programs',

    // Seating (app/Domain/Seating/Enums/Permission.php)
    ManageSeatPlans: 'manage_seat_plans',

    // Shop (app/Domain/Shop/Enums/Permission.php)
    ViewOrders: 'view_orders',
    ManageOrders: 'manage_orders',
    ManageVouchers: 'manage_vouchers',
    ManageShopConditions: 'manage_shop_conditions',

    // Sponsoring (app/Domain/Sponsoring/Enums/Permission.php)
    ManageSponsors: 'manage_sponsors',
    ManageSponsorLevels: 'manage_sponsor_levels',
    ManageAssignedSponsors: 'manage_assigned_sponsors',

    // Orga-Team (app/Domain/OrgaTeam/Enums/Permission.php)
    ManageOrgaTeams: 'manage_orga_teams',

    // Policy (app/Domain/Policy/Enums/Permission.php)
    ManagePolicies: 'manage_policies',

    // Ticketing (app/Domain/Ticketing/Enums/Permission.php)
    ManageTicketing: 'manage_ticketing',
    CheckInTickets: 'check_in_tickets',

    // Venues (app/Domain/Venue/Enums/Permission.php)
    ManageVenues: 'manage_venues',

    // Webhooks (app/Domain/Webhook/Enums/Permission.php)
    ManageWebhooks: 'manage_webhooks',

    // Orchestration (app/Domain/Orchestration/Enums/Permission.php)
    ManageGameServers: 'manage_game_servers',
    ViewOrchestration: 'view_orchestration',
} as const;

export type PermissionValue = (typeof Permission)[keyof typeof Permission];
