<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin } from 'lucide-vue-next';
import BannerCarousel from '@/components/BannerCarousel.vue';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { login, register } from '@/routes';
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

defineProps<{
    events: PaginatedEvents;
}>();

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
    <Head title="Upcoming Events" />

    <div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <!-- Header -->
        <header class="border-b bg-white dark:bg-[#161615]">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
            >
                <h1 class="text-xl font-semibold text-foreground">
                    Upcoming Events
                </h1>
                <nav class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        href="/dashboard"
                        class="text-sm text-muted-foreground hover:text-foreground"
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
                            :href="register()"
                            class="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Content -->
        <main class="mx-auto max-w-6xl px-6 py-10">
            <div
                v-if="events.data.length === 0"
                class="flex flex-col items-center justify-center py-20 text-center"
            >
                <CalendarDays class="mb-4 size-12 text-muted-foreground/50" />
                <h2 class="text-lg font-medium text-foreground">
                    No upcoming events
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Check back later for new events.
                </p>
            </div>

            <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="event in events.data"
                    :key="event.id"
                    class="flex flex-col overflow-hidden"
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
