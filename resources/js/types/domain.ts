export type Game = {
    id: number;
    name: string;
    slug: string;
    publisher: string | null;
    description: string | null;
    is_active: boolean;
    game_modes_count?: number;
    game_modes?: GameMode[];
    created_at: string;
    updated_at: string;
};

export type GameMode = {
    id: number;
    game_id: number;
    name: string;
    slug: string;
    description: string | null;
    team_size: number;
    parameters: Record<string, unknown> | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

// Competition Domain

export type CompetitionStatus =
    | 'draft'
    | 'registration_open'
    | 'registration_closed'
    | 'running'
    | 'finished'
    | 'archived';

export type CompetitionType = 'tournament' | 'league' | 'race';

export type Competition = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: CompetitionType;
    stage_type: string;
    status: CompetitionStatus;
    team_size: number | null;
    max_teams: number | null;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    starts_at: string | null;
    ends_at: string | null;
    event_id: number | null;
    event?: { id: number; name: string } | null;
    game_id: number | null;
    game?: Game | null;
    game_mode_id: number | null;
    game_mode?: GameMode | null;
    teams?: CompetitionTeam[];
    teams_count?: number;
    lanbrackets_id: number | null;
    lanbrackets_share_token: string | null;
    settings: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type CompetitionTeam = {
    id: number;
    competition_id: number;
    name: string;
    tag: string | null;
    captain_user_id: number | null;
    captain?: { id: number; name: string } | null;
    lanbrackets_id: number | null;
    active_members?: CompetitionTeamMember[];
    active_members_count?: number;
    created_at: string;
    updated_at: string;
};

export type CompetitionTeamMember = {
    id: number;
    team_id: number;
    user_id: number;
    user?: { id: number; name: string; email: string };
    joined_at: string | null;
    left_at: string | null;
    created_at: string;
    updated_at: string;
};

export type MatchResultProof = {
    id: number;
    competition_id: number;
    lanbrackets_match_id: number;
    submitted_by_user_id: number;
    submitted_by_team_id: number | null;
    screenshot_path: string;
    scores: { participant_id: number; score: number }[];
    is_disputed: boolean;
    resolved_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Address = {
    id: number;
    street: string;
    city: string;
    zip_code: string;
    state: string | null;
    country: string;
    created_at: string;
    updated_at: string;
};

export type VenueImage = {
    id: number;
    venue_id: number;
    path: string;
    url: string;
    alt_text: string | null;
    sort_order: number;
    created_at: string;
    updated_at: string;
};

export type Venue = {
    id: number;
    name: string;
    description: string | null;
    address_id: number;
    address: Address;
    images: VenueImage[];
    created_at: string;
    updated_at: string;
};

export type Event = {
    id: number;
    name: string;
    description: string | null;
    start_date: string;
    end_date: string;
    banner_images: string[];
    banner_image_urls: string[];
    status: 'draft' | 'published';
    seat_capacity: number | null;
    venue_id: number | null;
    venue: Venue | null;
    primary_program_id: number | null;
    programs: Program[];
    sponsors: Sponsor[];
    seat_plans?: SeatPlan[];
    taken_seats?: {
        seat_plan_id: number;
        seat_id: string;
        name: string | null;
    }[];
    created_at: string;
    updated_at: string;
};

export type Program = {
    id: number;
    name: string;
    description: string | null;
    visibility: 'public' | 'internal' | 'private';
    event_id: number;
    event?: { id: number; name: string };
    sort_order: number;
    time_slots: TimeSlot[];
    sponsors: Sponsor[];
    created_at: string;
    updated_at: string;
};

export type TimeSlot = {
    id?: number;
    name: string;
    description: string | null;
    starts_at: string;
    visibility: 'public' | 'internal' | 'private';
    program_id?: number;
    sort_order: number;
    sponsors: Sponsor[];
    created_at?: string;
    updated_at?: string;
};

export type SponsorLevel = {
    id: number;
    name: string;
    color: string;
    sort_order: number;
    sponsors_count?: number;
    created_at: string;
    updated_at: string;
};

export type Sponsor = {
    id: number;
    name: string;
    description: string | null;
    link: string | null;
    logo: string | null;
    logo_url: string | null;
    sponsor_level_id: number | null;
    sponsor_level: SponsorLevel | null;
    events: { id: number; name: string }[];
    managers: { id: number; name: string; email: string }[];
    created_at: string;
    updated_at: string;
};

// Ticketing Domain

export type TicketCategory = {
    id: number;
    name: string;
    description: string | null;
    sort_order: number;
    event_id: number | null;
    event?: Event | null;
    ticket_types_count?: number;
    created_at: string;
    updated_at: string;
};

export type TicketGroup = {
    id: number;
    name: string;
    description: string | null;
    event_id: number;
    event?: { id: number; name: string };
    created_at: string;
    updated_at: string;
};

export type TicketType = {
    id: number;
    name: string;
    description: string | null;
    price: number;
    quota: number;
    max_per_user: number | null;
    seats_per_user: number;
    max_users_per_ticket: number;
    check_in_mode: 'individual' | 'group';
    /** @deprecated Retained for backward compatibility — not shown in UI */
    is_row_ticket?: boolean;
    is_seatable: boolean;
    is_hidden: boolean;
    purchase_from: string | null;
    purchase_until: string | null;
    is_locked: boolean;
    event_id: number;
    event?: { id: number; name: string };
    ticket_category_id: number | null;
    ticket_category?: TicketCategory | null;
    ticket_group_id: number | null;
    ticket_group?: TicketGroup | null;
    tickets_count?: number;
    remaining_quota?: number;
    created_at: string;
    updated_at: string;
};

export type TicketAddon = {
    id: number;
    name: string;
    description: string | null;
    price: number;
    quota: number | null;
    seats_consumed: number;
    requires_ticket: boolean;
    is_hidden: boolean;
    event_id: number;
    event?: { id: number; name: string };
    tickets_count?: number;
    remaining_quota?: number;
    created_at: string;
    updated_at: string;
};

export type VoucherType = 'fixed_amount' | 'percentage';

export type Voucher = {
    id: number;
    code: string;
    type: VoucherType;
    discount_amount: number | null;
    discount_percent: number | null;
    max_uses: number | null;
    times_used: number;
    valid_from: string | null;
    valid_until: string | null;
    is_active: boolean;
    event_id: number | null;
    event?: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
};

export type PurchaseRequirement = {
    id: number;
    name: string;
    description: string | null;
    requirements_content: string | null;
    acknowledgements: string[] | null;
    is_active: boolean;
    requires_scroll: boolean;
    ticket_types_count?: number;
    addons_count?: number;
    ticket_types?: TicketType[];
    addons?: TicketAddon[];
    created_at: string;
    updated_at: string;
};

export type GlobalPurchaseCondition = {
    id: number;
    name: string;
    description: string | null;
    content: string | null;
    acknowledgement_label: string;
    is_required: boolean;
    is_active: boolean;
    requires_scroll: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
};

export type PaymentProviderCondition = {
    id: number;
    payment_method: PaymentMethod;
    name: string;
    description: string | null;
    content: string | null;
    acknowledgement_label: string;
    is_required: boolean;
    is_active: boolean;
    requires_scroll: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
};

export type OrderStatus = 'pending' | 'completed' | 'failed' | 'refunded';

export type PaymentMethod = 'stripe' | 'on_site';

export type Order = {
    id: number;
    payment_method: PaymentMethod;
    provider_session_id: string | null;
    provider_transaction_id: string | null;
    status: OrderStatus;
    paid_at: string | null;
    subtotal: number;
    discount: number;
    total: number;
    user_id: number;
    event_id: number;
    voucher_id: number | null;
    user?: { id: number; name: string; email: string };
    event?: { id: number; name: string };
    voucher?: Voucher | null;
    tickets?: Ticket[];
    order_lines?: OrderLine[];
    metadata?: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type OrderLine = {
    id: number;
    order_id: number;
    purchasable_type: string;
    purchasable_id: number;
    description: string;
    quantity: number;
    unit_price: number;
    total_price: number;
    created_at: string;
    updated_at: string;
};

export type TicketStatus = 'Active' | 'CheckedIn' | 'Cancelled';

export type Ticket = {
    id: number;
    status: TicketStatus;
    validation_kid: string | null;
    validation_issued_at: string | null;
    validation_expires_at: string | null;
    checked_in_at: string | null;
    ticket_type_id: number;
    event_id: number;
    order_id: number;
    owner_id: number;
    manager_id: number | null;
    ticket_type?: TicketType;
    event?: {
        id: number;
        name: string;
        start_date?: string;
        end_date?: string;
        banner_images?: string[];
        banner_image_urls?: string[];
    };
    order?: Order;
    owner?: { id: number; name: string; email: string };
    manager?: { id: number; name: string; email: string } | null;
    users?: {
        id: number;
        name: string;
        email: string;
        pivot?: { checked_in_at: string | null };
    }[];
    addons?: TicketAddon[];
    seat_assignments?: SeatAssignment[];
    created_at: string;
    updated_at: string;
};

export type SeatAssignment = {
    id: number;
    ticket_id: number;
    user_id: number;
    seat_plan_id: number;
    seat_id: string;
    seat_title: string | null;
    created_at?: string;
    updated_at?: string;
};

export type SeatPlanBlock = {
    id: string;
    title: string;
    color: string;
    seats: SeatPlanSeat[];
    labels: SeatPlanLabel[];
    /**
     * Per-block ticket-category allowlist (SET-F-011).
     * Empty / missing = open to all categories (permissive default).
     */
    allowed_ticket_category_ids?: number[] | null;
};

export type SeatPlanSeat = {
    id: number | string;
    title: string;
    x: number;
    y: number;
    salable: boolean;
    selected?: boolean;
    note?: string;
    color?: string;
    custom_data?: Record<string, unknown>;
};

export type SeatPlanLabel = {
    title: string;
    x: number;
    y: number;
};

export type SeatPlanData = {
    blocks: SeatPlanBlock[];
} & Record<string, unknown>;

export type SeatPlan = {
    id: number;
    name: string;
    event_id: number;
    data: SeatPlanData;
    event?: { id: number; name: string };
    created_at: string;
    updated_at: string;
};

// Auditing

export type Audit = {
    id: number;
    event: string;
    old_values: Record<string, unknown>;
    new_values: Record<string, unknown>;
    url: string | null;
    ip_address: string | null;
    user_agent: string | null;
    tags: string | null;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
};

// News Domain

export type NewsArticle = {
    id: number;
    title: string;
    slug: string;
    summary: string | null;
    content: string | null;
    tags: string[] | null;
    image: string | null;
    image_url: string | null;
    visibility: 'draft' | 'internal' | 'public';
    is_archived: boolean;
    comments_enabled: boolean;
    comments_require_approval: boolean;
    notify_users: boolean;
    meta_title: string | null;
    meta_description: string | null;
    og_title: string | null;
    og_description: string | null;
    og_image: string | null;
    og_image_url: string | null;
    author_id: number;
    author?: { id: number; name: string };
    published_at: string | null;
    comments?: NewsComment[];
    created_at: string;
    updated_at: string;
};

export type NewsComment = {
    id: number;
    news_article_id: number;
    user_id: number;
    content: string;
    is_approved: boolean;
    edited_at: string | null;
    article?: {
        id: number;
        title: string;
        slug: string;
        visibility: string;
        tags: string[] | null;
    };
    user?: { id: number; name: string };
    vote_score?: number;
    created_at: string;
    updated_at: string;
};

// Announcement Domain

export type AnnouncementPriority = 'silent' | 'normal' | 'emergency';

export type Announcement = {
    id: number;
    title: string;
    description: string | null;
    priority: AnnouncementPriority;
    event_id: number;
    event?: { id: number; name: string };
    author_id: number;
    author?: { id: number; name: string };
    published_at: string | null;
    dismissed_by_users_count?: number;
    dismissed_by_users?: { id: number; name: string }[];
    created_at: string;
    updated_at: string;
};

// Notification Domain

export type AppNotification = {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
};

// Achievements Domain

export type Achievement = {
    id: number;
    name: string;
    description: string | null;
    notification_text: string | null;
    color: string;
    icon: string;
    is_active: boolean;
    users_count?: number;
    event_classes?: string[];
    created_at: string;
    updated_at: string;
};

export type GrantableEvent = {
    value: string;
    label: string;
};

// Webhook Domain

export type WebhookEventType =
    | 'user.registered'
    | 'announcement.published'
    | 'news_article.published'
    | 'event.published';

export type Webhook = {
    id: number;
    name: string;
    url: string;
    event: WebhookEventType;
    secret: string | null;
    description: string | null;
    is_active: boolean;
    sent_count: number;
    integration_app_id: number | null;
    integration_app: { id: number; name: string; slug: string } | null;
    deliveries_count: number;
    last_delivery_status_code: number | null;
    created_at: string;
    updated_at: string;
};

export type WebhookDelivery = {
    id: number;
    webhook_id: number;
    status_code: number | null;
    duration_ms: number | null;
    succeeded: boolean;
    fired_at: string;
};

// Orchestration Domain

export type GameServerStatus =
    | 'available'
    | 'in_use'
    | 'offline'
    | 'maintenance';

export type GameServerAllocationType = 'competition' | 'casual' | 'flexible';

export type OrchestrationJobStatus =
    | 'pending'
    | 'selecting_server'
    | 'deploying'
    | 'active'
    | 'completed'
    | 'failed'
    | 'cancelled';

export type GameServer = {
    id: number;
    name: string;
    host: string;
    port: number;
    game_id: number;
    game_mode_id: number | null;
    status: GameServerStatus;
    allocation_type: GameServerAllocationType;
    credentials: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    game?: Game;
    game_mode?: GameMode | null;
    active_orchestration_job?: OrchestrationJob | null;
    created_at: string;
    updated_at: string;
};

export type OrchestrationJob = {
    id: number;
    game_server_id: number | null;
    competition_id: number;
    lanbrackets_match_id: number;
    game_id: number;
    game_mode_id: number | null;
    status: OrchestrationJobStatus;
    match_config: Record<string, unknown> | null;
    match_handler: string | null;
    error_message: string | null;
    attempts: number;
    started_at: string | null;
    completed_at: string | null;
    game_server?: GameServer | null;
    competition?: Competition;
    game?: Game;
    game_mode?: GameMode | null;
    chat_messages?: MatchChatMessage[];
    created_at: string;
    updated_at: string;
};

export type MatchChatMessage = {
    id: number;
    orchestration_job_id: number;
    steam_id: string;
    player_name: string;
    message: string;
    is_team_chat: boolean;
    timestamp: string;
    created_at: string;
};
