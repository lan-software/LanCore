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
    venue_id: number | null
    venue: Venue | null
    primary_program_id: number | null
    programs: Program[]
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
    created_at?: string
    updated_at?: string
}
