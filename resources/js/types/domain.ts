export type Game = {
    id: number
    name: string
    slug: string
    publisher: string | null
    description: string | null
    is_active: boolean
    game_modes_count?: number
    game_modes?: GameMode[]
    created_at: string
    updated_at: string
}

export type GameMode = {
    id: number
    game_id: number
    name: string
    slug: string
    description: string | null
    team_size: number
    parameters: Record<string, unknown> | null
    is_active: boolean
    created_at: string
    updated_at: string
}

export type Address = {
    id: number
    street: string
    city: string
    zip_code: string
    state: string | null
    country: string
    created_at: string
    updated_at: string
}

export type VenueImage = {
    id: number
    venue_id: number
    path: string
    url: string
    alt_text: string | null
    sort_order: number
    created_at: string
    updated_at: string
}

export type Venue = {
    id: number
    name: string
    description: string | null
    address_id: number
    address: Address
    images: VenueImage[]
    created_at: string
    updated_at: string
}

export type Event = {
    id: number
    name: string
    description: string | null
    start_date: string
    end_date: string
    banner_image: string | null
    banner_image_url: string | null
    status: 'draft' | 'published'
    seat_capacity: number | null
    venue_id: number | null
    venue: Venue | null
    primary_program_id: number | null
    programs: Program[]
    sponsors: Sponsor[]
    seat_plans?: SeatPlan[]
    created_at: string
    updated_at: string
}

export type Program = {
    id: number
    name: string
    description: string | null
    visibility: 'public' | 'internal' | 'private'
    event_id: number
    event?: { id: number; name: string }
    sort_order: number
    time_slots: TimeSlot[]
    sponsors: Sponsor[]
    created_at: string
    updated_at: string
}

export type TimeSlot = {
    id?: number
    name: string
    description: string | null
    starts_at: string
    visibility: 'public' | 'internal' | 'private'
    program_id?: number
    sort_order: number
    sponsors: Sponsor[]
    created_at?: string
    updated_at?: string
}

export type SponsorLevel = {
    id: number
    name: string
    color: string
    sort_order: number
    sponsors_count?: number
    created_at: string
    updated_at: string
}

export type Sponsor = {
    id: number
    name: string
    description: string | null
    link: string | null
    logo: string | null
    logo_url: string | null
    sponsor_level_id: number | null
    sponsor_level: SponsorLevel | null
    events: { id: number; name: string }[]
    managers: { id: number; name: string; email: string }[]
    created_at: string
    updated_at: string
}

// Ticketing Domain

export type TicketCategory = {
    id: number
    name: string
    description: string | null
    sort_order: number
    event_id: number | null
    event?: Event | null
    ticket_types_count?: number
    created_at: string
    updated_at: string
}

export type TicketGroup = {
    id: number
    name: string
    description: string | null
    event_id: number
    event?: { id: number; name: string }
    created_at: string
    updated_at: string
}

export type TicketType = {
    id: number
    name: string
    description: string | null
    price: number
    quota: number
    max_per_user: number | null
    seats_per_ticket: number
    is_row_ticket: boolean
    is_seatable: boolean
    is_hidden: boolean
    purchase_from: string | null
    purchase_until: string | null
    is_locked: boolean
    event_id: number
    event?: { id: number; name: string }
    ticket_category_id: number | null
    ticket_category?: TicketCategory | null
    ticket_group_id: number | null
    ticket_group?: TicketGroup | null
    tickets_count?: number
    remaining_quota?: number
    created_at: string
    updated_at: string
}

export type TicketAddon = {
    id: number
    name: string
    description: string | null
    price: number
    quota: number | null
    seats_consumed: number
    requires_ticket: boolean
    is_hidden: boolean
    event_id: number
    event?: { id: number; name: string }
    tickets_count?: number
    remaining_quota?: number
    created_at: string
    updated_at: string
}

export type VoucherType = 'fixed_amount' | 'percentage'

export type Voucher = {
    id: number
    code: string
    type: VoucherType
    discount_amount: number | null
    discount_percent: number | null
    max_uses: number | null
    times_used: number
    valid_from: string | null
    valid_until: string | null
    is_active: boolean
    event_id: number | null
    event?: { id: number; name: string } | null
    created_at: string
    updated_at: string
}

export type OrderStatus = 'Pending' | 'Completed' | 'Failed' | 'Refunded'

export type PaymentMethod = 'stripe' | 'on_site'

export type Order = {
    id: number
    payment_method: PaymentMethod
    provider_session_id: string | null
    provider_transaction_id: string | null
    status: OrderStatus
    subtotal: number
    discount: number
    total: number
    user_id: number
    event_id: number
    voucher_id: number | null
    user?: { id: number; name: string; email: string }
    event?: { id: number; name: string }
    voucher?: Voucher | null
    tickets?: Ticket[]
    created_at: string
    updated_at: string
}

export type TicketStatus = 'Active' | 'CheckedIn' | 'Cancelled'

export type Ticket = {
    id: number
    status: TicketStatus
    checked_in_at: string | null
    ticket_type_id: number
    event_id: number
    order_id: number
    owner_id: number
    manager_id: number | null
    user_id: number | null
    ticket_type?: TicketType
    event?: { id: number; name: string }
    order?: Order
    owner?: { id: number; name: string; email: string }
    manager?: { id: number; name: string; email: string } | null
    ticket_user?: { id: number; name: string; email: string } | null
    addons?: TicketAddon[]
    created_at: string
    updated_at: string
}

export type SeatPlanBlock = {
    id: string
    title: string
    color: string
    seats: SeatPlanSeat[]
    labels: SeatPlanLabel[]
}

export type SeatPlanSeat = {
    id: number | string
    title: string
    x: number
    y: number
    salable: boolean
    selected?: boolean
    note?: string
    color?: string
    custom_data?: Record<string, unknown>
}

export type SeatPlanLabel = {
    title: string
    x: number
    y: number
}

export type SeatPlanData = {
    blocks: SeatPlanBlock[]
} & Record<string, unknown>

export type SeatPlan = {
    id: number
    name: string
    event_id: number
    data: SeatPlanData
    event?: { id: number; name: string }
    created_at: string
    updated_at: string
}

// Auditing

export type Audit = {
    id: number
    event: string
    old_values: Record<string, unknown>
    new_values: Record<string, unknown>
    url: string | null
    ip_address: string | null
    user_agent: string | null
    tags: string | null
    created_at: string
    user: { id: number; name: string; email: string } | null
}

// News Domain

export type NewsArticle = {
    id: number
    title: string
    slug: string
    summary: string | null
    content: string | null
    tags: string[] | null
    image: string | null
    image_url: string | null
    visibility: 'draft' | 'internal' | 'public'
    is_archived: boolean
    comments_enabled: boolean
    comments_require_approval: boolean
    notify_users: boolean
    meta_title: string | null
    meta_description: string | null
    og_title: string | null
    og_description: string | null
    og_image: string | null
    og_image_url: string | null
    author_id: number
    author?: { id: number; name: string }
    published_at: string | null
    comments?: NewsComment[]
    created_at: string
    updated_at: string
}

export type NewsComment = {
    id: number
    news_article_id: number
    user_id: number
    content: string
    is_approved: boolean
    edited_at: string | null
    article?: { id: number; title: string; slug: string; visibility: string; tags: string[] | null }
    user?: { id: number; name: string }
    vote_score?: number
    created_at: string
    updated_at: string
}
