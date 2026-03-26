<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, defineAsyncComponent, type Component } from 'vue'
import { dashboard, login, register } from '@/routes'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar, Clock, MapPin, Newspaper, ShoppingCart, Megaphone, AlertTriangle, X, ExternalLink } from 'lucide-vue-next'
import { index as shopIndex } from '@/routes/shop'
import SeatMapCanvas from '@/components/SeatMapCanvas.vue'
import NotificationBell from '@/components/NotificationBell.vue'
import BannerCarousel from '@/components/BannerCarousel.vue'
import type { Event, NewsArticle, Announcement } from '@/types/domain'

const props = withDefaults(
    defineProps<{
        canRegister: boolean
        nextEvent: Event | null
        latestNews: NewsArticle[]
        announcements: Announcement[]
        dismissedAnnouncementIds: number[]
    }>(),
    {
        canRegister: true,
        latestNews: () => [],
        announcements: () => [],
        dismissedAnnouncementIds: () => [],
    },
)

const seatPlanData = computed(() => {
    return props.nextEvent?.seat_plans?.[0]?.data ?? null
})

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

function formatTime(dateString: string): string {
    return new Date(dateString).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    })
}

function formatDateTime(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

function dismissAnnouncement(announcementId: number) {
    router.post(`/announcements/${announcementId}/dismiss`, {}, { preserveScroll: true })
}

const page = usePage()
const integrationLinks = computed(() => page.props.integrationLinks ?? [])

const iconCache = new Map<string, Component>()

function resolveIcon(name: string | null): Component {
    if (!name) return ExternalLink
    if (iconCache.has(name)) return iconCache.get(name)!

    const pascalCase = name
        .split('-')
        .map((s) => s.charAt(0).toUpperCase() + s.slice(1))
        .join('')

    const asyncIcon = defineAsyncComponent({
        loader: () =>
            import('lucide-vue-next').then((mod) => {
                const icon = (mod as Record<string, Component>)[pascalCase]
                return icon ?? ExternalLink
            }),
        loadingComponent: ExternalLink,
    })

    iconCache.set(name, asyncIcon)
    return asyncIcon
}
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Header -->
        <header class="border-b">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <span class="text-lg font-semibold">LanCore</span>
                <nav class="flex items-center gap-4">
                    <Link
                        :href="shopIndex().url"
                        class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ShoppingCart class="size-4" />
                        Shop
                    </Link>
                    <a
                        v-for="link in integrationLinks"
                        :key="link.url"
                        :href="link.url"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <component :is="resolveIcon(link.icon)" class="size-4" />
                        {{ link.label }}
                    </a>
                    <NotificationBell v-if="$page.props.auth.user" />
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Next Upcoming Event -->
            <template v-if="nextEvent">
                <div class="mx-auto max-w-5xl px-6 py-12">
                    <!-- Event Hero -->
                    <div class="space-y-6">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider text-muted-foreground">Next Event</p>
                            <h1 class="mt-2 text-4xl font-bold tracking-tight">{{ nextEvent.name }}</h1>
                        </div>

                        <!-- Announcements -->
                        <div v-if="$page.props.auth.user && announcements.length > 0" class="space-y-3">
                            <div
                                v-for="announcement in announcements"
                                :key="announcement.id"
                                class="flex items-start gap-3 rounded-lg border p-4"
                                :class="{
                                    'border-destructive/50 bg-destructive/5': announcement.priority === 'emergency',
                                    'bg-muted/50': announcement.priority !== 'emergency',
                                }"
                            >
                                <AlertTriangle v-if="announcement.priority === 'emergency'" class="mt-0.5 size-5 shrink-0 text-destructive" />
                                <Megaphone v-else class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold">{{ announcement.title }}</span>
                                        <Badge v-if="announcement.priority === 'emergency'" variant="destructive" class="text-[10px]">Emergency</Badge>
                                    </div>
                                    <p v-if="announcement.description" class="mt-1 text-sm text-muted-foreground">
                                        {{ announcement.description }}
                                    </p>
                                </div>
                                <button
                                    @click.prevent="dismissAnnouncement(announcement.id)"
                                    class="shrink-0 rounded-md p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                                    title="Dismiss"
                                >
                                    <X class="size-4" />
                                </button>
                            </div>
                            <div class="flex justify-end">
                                <Link
                                    :href="`/events/${nextEvent.id}/announcements`"
                                    class="text-xs text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    View all announcements &rarr;
                                </Link>
                            </div>
                        </div>
                        <div v-else-if="$page.props.auth.user && dismissedAnnouncementIds.length > 0" class="flex justify-end">
                            <Link
                                :href="`/events/${nextEvent.id}/announcements`"
                                class="text-xs text-muted-foreground hover:text-foreground transition-colors"
                            >
                                <Megaphone class="inline size-3 mr-1" />
                                View announcements &rarr;
                            </Link>
                        </div>

                        <!-- Banner Image -->
                        <BannerCarousel
                            v-if="nextEvent.banner_image_urls.length > 0"
                            :images="nextEvent.banner_image_urls"
                            :alt="nextEvent.name"
                            class="max-h-80"
                        />

                        <!-- Key Details Grid -->
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <!-- Date & Time -->
                            <div class="flex items-start gap-3 rounded-lg border p-4">
                                <Calendar class="mt-0.5 size-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Date & Time</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(nextEvent.start_date) }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatTime(nextEvent.start_date) }} – {{ formatTime(nextEvent.end_date) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Venue -->
                            <div v-if="nextEvent.venue" class="flex items-start gap-3 rounded-lg border p-4">
                                <MapPin class="mt-0.5 size-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">{{ nextEvent.venue.name }}</p>
                                    <template v-if="nextEvent.venue.address">
                                        <p class="text-sm text-muted-foreground">{{ nextEvent.venue.address.street }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ nextEvent.venue.address.zip_code }} {{ nextEvent.venue.address.city }}
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <p v-if="nextEvent.description" class="text-muted-foreground leading-relaxed">
                            {{ nextEvent.description }}
                        </p>

                        <!-- Programs & Time Slots -->
                        <div v-if="nextEvent.programs && nextEvent.programs.length > 0" class="space-y-6">
                            <h2 class="text-2xl font-semibold">Program</h2>

                            <div v-for="program in nextEvent.programs" :key="program.id" class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-medium">{{ program.name }}</h3>
                                    <Badge v-if="nextEvent.primary_program_id === program.id" variant="default">Primary</Badge>
                                </div>
                                <p v-if="program.sponsors && program.sponsors.length > 0" class="text-sm italic text-muted-foreground">
                                    presented by {{ program.sponsors.map((s: { name: string }) => s.name).join(', ') }}
                                </p>
                                <p v-if="program.description" class="text-sm text-muted-foreground">
                                    {{ program.description }}
                                </p>

                                <!-- Time Slots -->
                                <div v-if="program.time_slots && program.time_slots.length > 0" class="rounded-lg border">
                                    <div
                                        v-for="(slot, index) in program.time_slots"
                                        :key="slot.id"
                                        class="flex items-start gap-3 p-4"
                                        :class="{ 'border-t': index > 0 }"
                                    >
                                        <Clock class="mt-0.5 size-4 text-muted-foreground" />
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium">{{ slot.name }}</span>
                                                <span class="text-xs text-muted-foreground">{{ formatDateTime(slot.starts_at) }}</span>
                                            </div>
                                            <p v-if="slot.sponsors && slot.sponsors.length > 0" class="text-xs italic text-muted-foreground">
                                                presented by {{ slot.sponsors.map((s: { name: string }) => s.name).join(', ') }}
                                            </p>
                                            <p v-if="slot.description" class="mt-1 text-sm text-muted-foreground">
                                                {{ slot.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Venue Images -->
                        <div v-if="nextEvent.venue?.images && nextEvent.venue.images.length > 0" class="space-y-4">
                            <h2 class="text-2xl font-semibold">Venue</h2>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <img
                                    v-for="image in nextEvent.venue.images"
                                    :key="image.id"
                                    :src="image.url"
                                    :alt="image.alt_text ?? nextEvent.venue.name"
                                    class="rounded-lg border object-cover aspect-video w-full"
                                />
                            </div>
                        </div>

                        <!-- Sponsors -->
                        <div v-if="nextEvent.sponsors && nextEvent.sponsors.length > 0" class="space-y-4">
                            <h2 class="text-2xl font-semibold">Sponsors</h2>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <a
                                    v-for="sponsor in nextEvent.sponsors"
                                    :key="sponsor.id"
                                    :href="sponsor.link ?? undefined"
                                    :target="sponsor.link ? '_blank' : undefined"
                                    :rel="sponsor.link ? 'noopener noreferrer' : undefined"
                                    class="flex flex-col items-center gap-3 rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                    :class="{ 'cursor-default': !sponsor.link }"
                                >
                                    <img
                                        v-if="sponsor.logo_url"
                                        :src="sponsor.logo_url"
                                        :alt="sponsor.name"
                                        class="h-16 w-auto object-contain"
                                    />
                                    <div class="text-center">
                                        <p class="text-sm font-medium">{{ sponsor.name }}</p>
                                        <Badge
                                            v-if="sponsor.sponsor_level"
                                            variant="outline"
                                            :style="{
                                                borderColor: sponsor.sponsor_level.color,
                                                color: sponsor.sponsor_level.color,
                                            }"
                                            class="mt-1"
                                        >
                                            {{ sponsor.sponsor_level.name }}
                                        </Badge>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Seat Map -->
                        <div v-if="seatPlanData" class="space-y-4">
                            <h2 class="text-2xl font-semibold">Seat Map</h2>
                            <div class="rounded-xl border" style="height: 500px">
                                <SeatMapCanvas
                                    :data="seatPlanData"
                                    :options="{
                                        legend: true,
                                        style: {
                                            seat: {
                                                hover: '#8fe100',
                                                color: '#6796ff',
                                                not_salable: '#424747',
                                            },
                                        },
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- No Upcoming Event -->
            <template v-else>
                <div class="flex flex-1 items-center justify-center px-6 py-24">
                    <div class="text-center space-y-4">
                        <Calendar class="mx-auto size-12 text-muted-foreground" />
                        <h1 class="text-3xl font-bold tracking-tight">No Upcoming Events</h1>
                        <p class="text-muted-foreground max-w-md mx-auto">
                            There are currently no upcoming events scheduled. Check back later for updates.
                        </p>
                    </div>
                </div>
            </template>
        </main>

        <!-- Latest News -->
        <section v-if="latestNews.length > 0" class="border-t">
            <div class="mx-auto max-w-5xl px-6 py-12">
                <div class="space-y-6">
                    <div class="flex items-center gap-2">
                        <Newspaper class="size-5 text-muted-foreground" />
                        <h2 class="text-2xl font-bold tracking-tight">Latest News</h2>
                    </div>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="article in latestNews"
                            :key="article.id"
                            :href="`/news/${article.slug}`"
                            class="group rounded-lg border transition-colors hover:border-foreground/20"
                        >
                            <img
                                v-if="article.image_url"
                                :src="article.image_url"
                                :alt="article.title"
                                class="h-40 w-full rounded-t-lg object-cover"
                            />
                            <div class="p-4 space-y-2">
                                <h3 class="font-semibold group-hover:text-primary transition-colors line-clamp-2">{{ article.title }}</h3>
                                <p v-if="article.summary" class="text-sm text-muted-foreground line-clamp-3">{{ article.summary }}</p>
                                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <span v-if="article.published_at">{{ new Date(article.published_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) }}</span>
                                    <span v-if="article.author">· {{ article.author.name }}</span>
                                </div>
                                <div v-if="article.tags && article.tags.length > 0" class="flex flex-wrap gap-1">
                                    <Badge v-for="tag in article.tags.slice(0, 3)" :key="tag" variant="secondary" class="text-[10px]">{{ tag }}</Badge>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t">
            <div class="mx-auto max-w-5xl px-6 py-6 text-center text-sm text-muted-foreground">
                Powered by LanCore 2026
            </div>
        </footer>
    </div>
</template>
