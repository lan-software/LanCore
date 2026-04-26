<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
    isPreview: boolean;
    isOwner: boolean;
}>();

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

            <div class="mx-auto -mt-16 max-w-4xl px-4">
                <div class="flex items-end gap-4">
                    <img
                        :src="profile.avatar_url"
                        :alt="profile.username ?? ''"
                        class="h-32 w-32 rounded-2xl border-4 border-background object-cover shadow-lg"
                    />
                    <div class="flex-1 pb-2">
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
                    <Link
                        v-if="isOwner && !isPreview"
                        :href="profileEdit().url"
                        class="rounded-md border border-border bg-card px-3 py-1.5 text-sm font-medium hover:bg-muted"
                    >
                        {{ $t('publicProfile.editProfile') }}
                    </Link>
                </div>
            </div>
        </div>

        <div class="mx-auto mt-8 max-w-4xl space-y-8 px-4">
            <section
                v-if="profile.profile_description"
                class="rounded-xl border border-border bg-card p-6"
            >
                <p class="whitespace-pre-line text-sm leading-relaxed">
                    {{ profile.profile_description }}
                </p>
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

                <ul v-else class="divide-y divide-border">
                    <li
                        v-for="achievement in achievements"
                        :key="achievement.id"
                        class="flex items-center gap-4 py-3"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                            :style="
                                achievement.color
                                    ? { backgroundColor: achievement.color, color: '#fff' }
                                    : {}
                            "
                        >
                            <span class="text-lg">{{
                                achievement.icon ?? '🏆'
                            }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ achievement.name }}</p>
                            <p
                                v-if="achievement.description"
                                class="text-xs text-muted-foreground"
                            >
                                {{ achievement.description }}
                            </p>
                        </div>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="rarityClass(achievement.earned_percentage)"
                        >
                            {{
                                $t('publicProfile.earnedBy', {
                                    percent: achievement.earned_percentage,
                                })
                            }}
                        </span>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
