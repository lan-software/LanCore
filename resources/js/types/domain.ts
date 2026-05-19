export type Game = {
    id: string;
    name: string;
    slug: string;
    publisher: string | null;
    description: string | null;
    is_active: boolean;
    avg_match_minutes: number | null;
    avg_stage_minutes: number | null;
    match_length_minutes: number | null;
    game_modes_count?: number;
    game_modes?: GameMode[];
    created_at: string;
    updated_at: string;
};

export type GameMode = {
    id: string;
    game_id: string;
    name: string;
    slug: string;
    description: string | null;
    team_size: number;
    parameters: Record<string, unknown> | null;
    is_active: boolean;
    match_length_minutes: number | null;
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
    id: string;
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
    event_id: string | null;
    event?: { id: string; name: string } | null;
    game_id: string | null;
    game?: Game | null;
    game_mode_id: string | null;
    game_mode?: GameMode | null;
    teams?: CompetitionTeam[];
    teams_count?: number;
    lanbrackets_id: string | null;
    lanbrackets_share_token: string | null;
    settings: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    signup_rules?: Record<string, unknown> | null;
    match_length_minutes: number | null;
    created_at: string;
    updated_at: string;
};

export type CompetitionTeam = {
    id: string;
    competition_id: string;
    name: string;
    tag: string | null;
    captain_user_id: string | null;
    captain?: { id: string; name: string } | null;
    lanbrackets_id: string | null;
    active_members?: CompetitionTeamMember[];
    active_members_count?: number;
    created_at: string;
    updated_at: string;
};

export type CompetitionTeamMember = {
    id: string;
    team_id: string;
    user_id: string;
    user?: { id: string; name: string; email: string };
    joined_at: string | null;
    left_at: string | null;
    created_at: string;
    updated_at: string;
};

export type MatchResultProof = {
    id: string;
    competition_id: string;
    lanbrackets_match_id: string;
    submitted_by_user_id: string;
    submitted_by_team_id: string | null;
    screenshot_path: string;
    scores: { participant_id: string; score: number }[];
    is_disputed: boolean;
    resolved_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Address = {
    id: string;
    street: string;
    city: string;
    zip_code: string;
    state: string | null;
    country: string;
    created_at: string;
    updated_at: string;
};

export type VenueImage = {
    id: string;
    venue_id: string;
    path: string;
    url: string;
    alt_text: string | null;
    sort_order: number;
    created_at: string;
    updated_at: string;
};

export type Venue = {
    id: string;
    name: string;
    description: string | null;
    address_id: string;
    address: Address;
    images: VenueImage[];
    created_at: string;
    updated_at: string;
};

export type Event = {
    id: string;
    name: string;
    description: string | null;
    start_date: string;
    end_date: string;
    banner_images: string[];
    banner_image_urls: string[];
    status: 'draft' | 'published';
    seat_capacity: number | null;
    venue_id: string | null;
    venue: Venue | null;
    primary_program_id: string | null;
    programs: Program[];
    sponsors: Sponsor[];
    seat_plans?: SeatPlan[];
    taken_seats?: {
        seat_plan_id: string;
        seat_id: string;
        name: string | null;
        username: string | null;
        profile_emoji: string | null;
        short_bio: string | null;
        avatar_url: string | null;
        banner_url: string | null;
    }[];
    created_at: string;
    updated_at: string;
};

export type Program = {
    id: string;
    name: string;
    description: string | null;
    visibility: 'public' | 'internal' | 'private';
    event_id: string;
    event?: { id: string; name: string };
    sort_order: number;
    time_slots: TimeSlot[];
    sponsors: Sponsor[];
    created_at: string;
    updated_at: string;
};

export type TimeSlot = {
    id?: string;
    name: string;
    description: string | null;
    starts_at: string;
    visibility: 'public' | 'internal' | 'private';
    program_id?: string;
    sort_order: number;
    sponsors: Sponsor[];
    created_at?: string;
    updated_at?: string;
};

export type SponsorLevel = {
    id: string;
    name: string;
    color: string;
    sort_order: number;
    sponsors_count?: number;
    created_at: string;
    updated_at: string;
};

export type Sponsor = {
    id: string;
    name: string;
    description: string | null;
    link: string | null;
    logo: string | null;
    logo_url: string | null;
    sponsor_level_id: string | null;
    sponsor_level: SponsorLevel | null;
    events: { id: string; name: string }[];
    managers: { id: string; name: string; email: string }[];
    created_at: string;
    updated_at: string;
};

// Ticketing Domain

export type TicketCategory = {
    id: string;
    name: string;
    description: string | null;
    sort_order: number;
    event_id: string | null;
    event?: Event | null;
    ticket_types_count?: number;
    created_at: string;
    updated_at: string;
};

export type TicketGroup = {
    id: string;
    name: string;
    description: string | null;
    event_id: string;
    event?: { id: string; name: string };
    created_at: string;
    updated_at: string;
};

export type TicketType = {
    id: string;
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
    event_id: string;
    event?: { id: string; name: string };
    ticket_category_id: string | null;
    ticket_category?: TicketCategory | null;
    ticket_group_id: string | null;
    ticket_group?: TicketGroup | null;
    tickets_count?: number;
    remaining_quota?: number;
    notify_on_release: boolean;
    notify_on_end: boolean;
    notify_on_end_lead_minutes: number;
    release_notified_at: string | null;
    end_notified_at: string | null;
    created_at: string;
    updated_at: string;
};

export type TicketAddon = {
    id: string;
    name: string;
    description: string | null;
    price: number;
    quota: number | null;
    seats_consumed: number;
    requires_ticket: boolean;
    is_hidden: boolean;
    event_id: string;
    event?: { id: string; name: string };
    tickets_count?: number;
    remaining_quota?: number;
    created_at: string;
    updated_at: string;
};

export type VoucherType = 'fixed_amount' | 'percentage';

export type Voucher = {
    id: string;
    code: string;
    type: VoucherType;
    discount_amount: number | null;
    discount_percent: number | null;
    max_uses: number | null;
    times_used: number;
    valid_from: string | null;
    valid_until: string | null;
    is_active: boolean;
    event_id: string | null;
    event?: { id: string; name: string } | null;
    created_at: string;
    updated_at: string;
};

export type PurchaseRequirement = {
    id: string;
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
    id: string;
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
    id: string;
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

export type PaymentMethod = 'stripe' | 'on_site' | 'paypal';

export type Order = {
    id: string;
    payment_method: PaymentMethod;
    provider_session_id: string | null;
    provider_transaction_id: string | null;
    status: OrderStatus;
    paid_at: string | null;
    subtotal: number;
    discount: number;
    total: number;
    fee_amount: number | null;
    net_amount: number | null;
    fee_source: 'provider' | 'estimated' | null;
    fees_fetched_at: string | null;
    currency: string;
    user_id: string;
    event_id: string;
    voucher_id: string | null;
    user?: { id: string; name: string; email: string };
    event?: { id: string; name: string };
    voucher?: Voucher | null;
    tickets?: Ticket[];
    order_lines?: OrderLine[];
    metadata?: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type OrderLine = {
    id: string;
    order_id: string;
    purchasable_type: string;
    purchasable_id: string;
    description: string;
    quantity: number;
    unit_price: number;
    total_price: number;
    created_at: string;
    updated_at: string;
};

export type TicketStatus = 'Active' | 'CheckedIn' | 'Cancelled';

export type Ticket = {
    id: string;
    status: TicketStatus;
    validation_kid: string | null;
    validation_issued_at: string | null;
    validation_expires_at: string | null;
    checked_in_at: string | null;
    ticket_type_id: string;
    event_id: string;
    order_id: string;
    owner_id: string;
    manager_id: string | null;
    ticket_type?: TicketType;
    event?: {
        id: string;
        name: string;
        start_date?: string;
        end_date?: string;
        banner_images?: string[];
        banner_image_urls?: string[];
    };
    order?: Order;
    owner?: { id: string; name: string; email: string };
    manager?: { id: string; name: string; email: string } | null;
    users?: {
        id: string;
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
    id: string;
    ticket_id: string;
    user_id: string;
    seat_plan_id: string;
    seat_id: string;
    seat_title: string | null;
    created_at?: string;
    updated_at?: string;
};

/**
 * Blocks, rows, seats and labels live in their own tables since the normalization
 * migration. The client widens `id` to `number | string` so the editor can tag
 * freshly-created entities with `new-*` placeholders until the save response
 * swaps in the persisted PKs.
 */
export type SeatPlanBlock = {
    id: string | string;
    title: string;
    color: string;
    /**
     * Optional prefix prepended to every seat title in this block at render
     * time (e.g. "VIP-" turns "A1" into "VIP-A1"). Null/empty ⇒ no prefix.
     * Only stored on editor payloads; the public `SeatPlanResource` emits
     * seat titles with the prefix already baked in.
     */
    seat_title_prefix?: string | null;
    background_image_url?: string | null;
    sort_order?: number;
    rows?: SeatPlanRow[];
    seats: SeatPlanSeat[];
    labels: SeatPlanLabel[];
    /**
     * Per-block ticket-category allowlist (SET-F-011).
     * Empty / missing = open to all categories (permissive default).
     */
    allowed_ticket_category_ids?: number[] | null;
};

export type SeatPlanRow = {
    id: string | string;
    name: string;
    sort_order?: number;
};

export type SeatPlanSeat = {
    id: string | string;
    row_id?: string | string | null;
    number?: number | null;
    title: string;
    x: number;
    y: number;
    salable: boolean;
    selected?: boolean;
    note?: string | null;
    color?: string | null;
    custom_data?: Record<string, unknown> | null;
};

export type SeatPlanLabel = {
    id?: string | string;
    title: string;
    x: number;
    y: number;
    sort_order?: number;
};

export type SeatPlanData = {
    blocks: SeatPlanBlock[];
    /**
     * Plan-level labels (SET-F-020). Both the editor and the public
     * SeatPlanResource emit these at the top level; the renderer treats them
     * as siblings of `blocks`.
     */
    labels?: SeatPlanLabel[];
} & Record<string, unknown>;

export type SeatPlan = {
    id: string;
    name: string;
    event_id: string;
    background_image_url?: string | null;
    /** Plan-level labels (SET-F-020). */
    labels?: SeatPlanLabel[];
    blocks: SeatPlanBlock[];
    event?: { id: string; name: string };
    created_at?: string;
    updated_at?: string;
};

// Auditing

export type Audit = {
    id: string;
    event: string;
    old_values: Record<string, unknown>;
    new_values: Record<string, unknown>;
    url: string | null;
    ip_address: string | null;
    user_agent: string | null;
    tags: string | null;
    created_at: string;
    user: { id: string; name: string; email: string } | null;
};

// News Domain

export type NewsArticle = {
    id: string;
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
    author_id: string;
    author?: { id: string; name: string };
    published_at: string | null;
    comments?: NewsComment[];
    created_at: string;
    updated_at: string;
};

export type NewsComment = {
    id: string;
    news_article_id: string;
    user_id: string;
    content: string;
    is_approved: boolean;
    edited_at: string | null;
    article?: {
        id: string;
        title: string;
        slug: string;
        visibility: string;
        tags: string[] | null;
    };
    user?: { id: string; name: string };
    vote_score?: number;
    created_at: string;
    updated_at: string;
};

// Announcement Domain

export type AnnouncementPriority = 'silent' | 'normal' | 'emergency';

export type Announcement = {
    id: string;
    title: string;
    description: string | null;
    priority: AnnouncementPriority;
    event_id: string;
    event?: { id: string; name: string };
    author_id: string;
    author?: { id: string; name: string };
    published_at: string | null;
    dismissed_by_users_count?: number;
    dismissed_by_users?: { id: string; name: string }[];
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
    id: string;
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
    id: string;
    name: string;
    url: string;
    event: WebhookEventType;
    secret: string | null;
    description: string | null;
    is_active: boolean;
    sent_count: number;
    integration_app_id: string | null;
    integration_app: { id: string; name: string; slug: string } | null;
    deliveries_count: number;
    last_delivery_status_code: number | null;
    created_at: string;
    updated_at: string;
};

export type WebhookDelivery = {
    id: string;
    webhook_id: string;
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
    id: string;
    name: string;
    host: string;
    port: number;
    game_id: string;
    game_mode_id: string | null;
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
    id: string;
    game_server_id: string | null;
    competition_id: string;
    lanbrackets_match_id: string;
    game_id: string;
    game_mode_id: string | null;
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
    id: string;
    orchestration_job_id: string;
    steam_id: string;
    player_name: string;
    message: string;
    is_team_chat: boolean;
    timestamp: string;
    created_at: string;
};
