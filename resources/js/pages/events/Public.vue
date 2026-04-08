<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin } from 'lucide-vue-next';
import BannerCarousel from '@/components/BannerCarousel.vue';
import PublicTopbar from '@/components/PublicTopbar.vue';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Event } from '@/types/domain';

interface PaginatedEvents {
    data: Event[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = withDefaults(
    defineProps<{
        events: PaginatedEvents;
        mode?: 'upcoming' | 'past';
    }>(),
    { mode: 'upcoming' },
);

const isPast = props.mode === 'past';
const pageTitle = isPast ? 'Past Events' : 'Upcoming Events';
const emptyTitle = isPast ? 'No past events' : 'No upcoming events';
const emptyHint = isPast
    ? 'Past events will appear here once they have ended.'
    : 'Check back later for new events.';

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="pageTitle" />

    <div class="min-h-screen bg-background text-foreground">
        <PublicTopbar />

        <div class="mx-auto max-w-6xl px-6 pt-10">
            <h1 class="text-2xl font-semibold text-foreground">
                {{ pageTitle }}
            </h1>
        </div>

        <!-- Content -->
        <main class="mx-auto max-w-6xl px-6 py-10">
            <div
                v-if="events.data.length === 0"
                class="flex flex-col items-center justify-center py-20 text-center"
            >
                <CalendarDays class="mb-4 size-12 text-muted-foreground/50" />
                <h2 class="text-lg font-medium text-foreground">
                    {{ emptyTitle }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ emptyHint }}
                </p>
            </div>

            <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="event in events.data"
                    :key="event.id"
                    :href="`/events/${event.id}/public`"
                    class="block transition hover:opacity-90"
                >
                <Card
                    class="flex h-full flex-col overflow-hidden"
                >
                    <BannerCarousel
                        v-if="event.banner_image_urls.length > 0"
                        :images="event.banner_image_urls"
                        :alt="event.name"
                        class="h-48 rounded-none border-0 border-b"
                    />
                    <CardHeader>
                        <CardTitle class="line-clamp-2">{{
                            event.name
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="flex-1 space-y-3">
                        <div
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <CalendarDays class="size-4 shrink-0" />
                            <span>{{ formatDate(event.start_date) }}</span>
                        </div>
                        <div
                            v-if="event.venue"
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <MapPin class="size-4 shrink-0" />
                            <span>{{ event.venue.name }}</span>
                        </div>
                        <p
                            v-if="event.description"
                            class="line-clamp-3 text-sm text-muted-foreground"
                        >
                            {{ event.description }}
                        </p>
                    </CardContent>
                    <CardFooter class="text-xs text-muted-foreground">
                        {{ formatDate(event.start_date) }} &ndash;
                        {{ formatDate(event.end_date) }}
                    </CardFooter>
                </Card>
                </Link>
            </div>

            <!-- Pagination -->
            <nav
                v-if="events.last_page > 1"
                class="mt-10 flex items-center justify-center gap-1"
            >
                <template v-for="link in events.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md px-3 py-1.5 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                        "
                        ><span v-html="link.label"
                    /></Link>
                    <span
                        v-else
                        class="px-3 py-1.5 text-sm text-muted-foreground/50"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </main>
    </div>
</template>
