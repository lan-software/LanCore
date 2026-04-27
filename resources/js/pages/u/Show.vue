<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, ExternalLink, MapPin } from 'lucide-vue-next';
import { defineAsyncComponent } from 'vue';
import type { Component } from 'vue';
import { edit as profileEdit } from '@/routes/profile';

type Achievement = {
    id: number;
    name: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    earned_at: string | null;
    earned_user_count: number;
    earned_percentage: number;
};

type EventHistoryItem = {
    id: number;
    name: string;
    start_date: string | null;
    end_date: string | null;
    venue_name: string | null;
    public_url: string | null;
};

type ProfilePayload = {
    id: number;
    username: string | null;
    profile_emoji: string | null;
    short_bio: string | null;
    profile_description: string | null;
    avatar_url: string;
    banner_url: string | null;
    profile_visibility: 'public' | 'logged_in' | 'private';
    created_at: string | null;
};

defineProps<{
    profile: ProfilePayload;
    achievements: Achievement[];
    upcomingEvents: EventHistoryItem[];
    eventHistory: EventHistoryItem[];
    isPreview: boolean;
    isOwner: boolean;
}>();

function formatEventDateRange(
    start: string | null,
    end: string | null,
): string {
    if (!start) {
        return '';
    }

    const startDate = new Date(start);
    const endDate = end ? new Date(end) : null;

    const sameDay =
        endDate !== null &&
        startDate.getFullYear() === endDate.getFullYear() &&
        startDate.getMonth() === endDate.getMonth() &&
        startDate.getDate() === endDate.getDate();

    const opts: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    };

    if (endDate === null || sameDay) {
        return startDate.toLocaleDateString(undefined, opts);
    }

    return `${startDate.toLocaleDateString(undefined, opts)} – ${endDate.toLocaleDateString(undefined, opts)}`;
}

const iconCache = new Map<string, Component>();

function resolveIcon(name: string | null): Component {
    const key = name ?? 'trophy';

    if (iconCache.has(key)) {
        return iconCache.get(key)!;
    }

    const pascalCase = key
        .split('-')
        .map((s) => s.charAt(0).toUpperCase() + s.slice(1))
        .join('');

    const asyncIcon = defineAsyncComponent({
        loader: () =>
            import('lucide-vue-next').then((mod) => {
                const icon = (mod as Record<string, Component>)[pascalCase];

                return icon ?? ExternalLink;
            }),
        loadingComponent: ExternalLink,
    });

    iconCache.set(key, asyncIcon);

    return asyncIcon;
}

function rarityClass(percentage: number): string {
    if (percentage <= 5) {
        return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
    }

    if (percentage <= 20) {
        return 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-900/40 dark:text-fuchsia-200';
    }

    if (percentage <= 50) {
        return 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200';
    }

    return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
}
</script>

<template>
    <Head :title="profile.username ?? 'Profile'" />

    <div class="min-h-screen bg-background pb-12">
        <div
            v-if="isPreview"
            class="bg-amber-100 px-4 py-2 text-center text-sm font-medium text-amber-900"
        >
            {{ $t('publicProfile.previewIndicator') }}
        </div>

        <div class="relative">
            <div
                v-if="profile.banner_url"
                class="h-64 w-full bg-cover bg-center"
                :style="{ backgroundImage: `url(${profile.banner_url})` }"
            />
            <div
                v-else
                class="h-64 w-full bg-gradient-to-br from-fuchsia-500 via-violet-500 to-cyan-500"
            />

            <div class="mx-auto max-w-4xl px-4">
                <div class="-mt-16 flex items-end justify-between gap-4">
                    <img
                        :src="profile.avatar_url"
                        :alt="profile.username ?? ''"
                        class="relative z-10 h-32 w-32 rounded-2xl border-4 border-background object-cover shadow-lg"
                    />
                    <Link
                        v-if="isOwner && !isPreview"
                        :href="profileEdit().url"
                        class="mb-2 rounded-md border border-border bg-card px-3 py-1.5 text-sm font-medium hover:bg-muted"
                    >
                        {{ $t('publicProfile.editProfile') }}
                    </Link>
                </div>

                <div class="mt-4">
                    <h1 class="flex items-center gap-2 text-3xl font-bold">
                        <span v-if="profile.profile_emoji">{{
                            profile.profile_emoji
                        }}</span>
                        <span>{{ profile.username }}</span>
                    </h1>
                    <p
                        v-if="profile.short_bio"
                        class="mt-1 text-sm text-muted-foreground"
                    >
                        {{ profile.short_bio }}
                    </p>
                </div>
            </div>
        </div>

        <div class="mx-auto mt-8 max-w-4xl space-y-8 px-4">
            <section
                v-if="profile.profile_description"
                class="rounded-xl border border-border bg-card p-6"
            >
                <p class="text-sm leading-relaxed whitespace-pre-line">
                    {{ profile.profile_description }}
                </p>
            </section>

            <section class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                    <span
                        class="inline-block size-2 shrink-0 rounded-full bg-emerald-500"
                        aria-hidden="true"
                    />
                    {{ $t('publicProfile.upcomingEventsHeading') }}
                </h2>

                <div
                    v-if="upcomingEvents.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ $t('publicProfile.noUpcomingEvents') }}
                </div>

                <ul v-else class="divide-y divide-border">
                    <li
                        v-for="event in upcomingEvents"
                        :key="event.id"
                        class="flex flex-col gap-1 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
                    >
                        <div class="min-w-0 flex-1">
                            <component
                                :is="event.public_url ? 'a' : 'div'"
                                :href="event.public_url ?? undefined"
                                class="flex flex-wrap items-center gap-1.5 text-sm font-medium"
                                :class="
                                    event.public_url ? 'hover:underline' : ''
                                "
                            >
                                <span class="truncate">{{ event.name }}</span>
                                <span
                                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200"
                                >
                                    {{ $t('publicProfile.attendingBadge') }}
                                </span>
                                <ExternalLink
                                    v-if="event.public_url"
                                    class="size-3.5 shrink-0 text-muted-foreground"
                                />
                            </component>
                            <p
                                v-if="event.venue_name"
                                class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <MapPin class="size-3" />
                                <span class="truncate">{{
                                    event.venue_name
                                }}</span>
                            </p>
                        </div>
                        <p
                            class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground"
                        >
                            <CalendarDays class="size-3" />
                            {{
                                formatEventDateRange(
                                    event.start_date,
                                    event.end_date,
                                )
                            }}
                        </p>
                    </li>
                </ul>
            </section>

            <section class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 text-lg font-semibold">
                    {{ $t('publicProfile.eventHistoryHeading') }}
                </h2>

                <div
                    v-if="eventHistory.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ $t('publicProfile.noEventHistory') }}
                </div>

                <ul v-else class="divide-y divide-border">
                    <li
                        v-for="event in eventHistory"
                        :key="event.id"
                        class="flex flex-col gap-1 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
                    >
                        <div class="min-w-0 flex-1">
                            <component
                                :is="event.public_url ? 'a' : 'div'"
                                :href="event.public_url ?? undefined"
                                class="flex items-center gap-1.5 text-sm font-medium"
                                :class="
                                    event.public_url ? 'hover:underline' : ''
                                "
                            >
                                <span class="truncate">{{ event.name }}</span>
                                <ExternalLink
                                    v-if="event.public_url"
                                    class="size-3.5 shrink-0 text-muted-foreground"
                                />
                            </component>
                            <p
                                v-if="event.venue_name"
                                class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <MapPin class="size-3" />
                                <span class="truncate">{{
                                    event.venue_name
                                }}</span>
                            </p>
                        </div>
                        <p
                            class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground"
                        >
                            <CalendarDays class="size-3" />
                            {{
                                formatEventDateRange(
                                    event.start_date,
                                    event.end_date,
                                )
                            }}
                        </p>
                    </li>
                </ul>
            </section>

            <section class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 text-lg font-semibold">
                    {{ $t('publicProfile.achievementsHeading') }}
                </h2>

                <div
                    v-if="achievements.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ $t('publicProfile.noAchievements') }}
                </div>

                <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div
                        v-for="achievement in achievements"
                        :key="achievement.id"
                        class="flex flex-col items-center gap-3 rounded-xl border border-border bg-background p-5 text-center shadow-sm"
                    >
                        <div
                            class="flex size-14 shrink-0 items-center justify-center rounded-full"
                            :style="
                                achievement.color
                                    ? { backgroundColor: achievement.color }
                                    : {}
                            "
                        >
                            <component
                                :is="resolveIcon(achievement.icon)"
                                class="size-7 text-white"
                            />
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm leading-tight font-semibold">
                                {{ achievement.name }}
                            </p>
                            <p
                                v-if="achievement.description"
                                class="text-xs text-muted-foreground"
                            >
                                {{ achievement.description }}
                            </p>
                        </div>

                        <span
                            class="mt-auto rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="rarityClass(achievement.earned_percentage)"
                        >
                            {{
                                $t('publicProfile.earnedBy', {
                                    percent: achievement.earned_percentage,
                                })
                            }}
                        </span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
