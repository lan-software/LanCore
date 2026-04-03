<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Trophy, ExternalLink } from 'lucide-vue-next';
import { computed, defineAsyncComponent } from 'vue';
import type { Component } from 'vue';
import UserAchievementsController from '@/actions/App/Http/Controllers/Settings/UserAchievementsController';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

type EarnedAchievement = {
    id: number;
    name: string;
    description: string | null;
    color: string;
    icon: string;
    pivot: {
        earned_at: string;
    };
};

type AchievementSummary = {
    id: number;
    name: string;
    color: string;
    icon: string;
};

const props = defineProps<{
    earnedAchievements: EarnedAchievement[];
    allAchievements: AchievementSummary[];
}>();

const earnedIds = computed(
    () => new Set(props.earnedAchievements.map((a) => a.id)),
);

const unearnedAchievements = computed(() =>
    props.allAchievements.filter((a) => !earnedIds.value.has(a.id)),
);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Achievements',
        href: UserAchievementsController.url(),
    },
];

const iconCache = new Map<string, Component>();

function resolveIcon(name: string): Component {
    if (iconCache.has(name)) {
        return iconCache.get(name)!;
    }

    const pascalCase = name
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

    iconCache.set(name, asyncIcon);

    return asyncIcon;
}

function formatDate(dateString: string): string {
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(
        new Date(dateString),
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Achievements" />

        <h1 class="sr-only">Achievements</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Achievements"
                    description="Badges you've earned by using LanCore"
                />

                <div
                    class="flex items-center gap-2 rounded-lg border bg-card px-4 py-3 shadow-sm"
                >
                    <Trophy class="size-5 text-muted-foreground" />
                    <p class="text-sm font-medium">
                        {{ earnedAchievements.length }}
                        <span class="text-muted-foreground">
                            out of {{ allAchievements.length }}
                        </span>
                    </p>
                </div>

                <div
                    v-if="earnedAchievements.length > 0"
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3"
                >
                    <div
                        v-for="achievement in earnedAchievements"
                        :key="achievement.id"
                        class="flex flex-col items-center gap-3 rounded-xl border bg-card p-5 text-center shadow-sm"
                    >
                        <div
                            class="flex size-14 shrink-0 items-center justify-center rounded-full"
                            :style="{ backgroundColor: achievement.color }"
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

                        <p class="mt-auto text-xs text-muted-foreground">
                            Earned {{ formatDate(achievement.pivot.earned_at) }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="unearnedAchievements.length > 0"
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3"
                >
                    <div
                        v-for="achievement in unearnedAchievements"
                        :key="achievement.id"
                        class="flex flex-col items-center gap-3 rounded-xl border border-dashed bg-muted/30 p-5 text-center opacity-50"
                    >
                        <div
                            class="flex size-14 shrink-0 items-center justify-center rounded-full bg-muted"
                        >
                            <component
                                :is="resolveIcon(achievement.icon)"
                                class="size-7 text-muted-foreground"
                            />
                        </div>

                        <p
                            class="text-sm leading-tight font-semibold text-muted-foreground"
                        >
                            {{ achievement.name }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="
                        earnedAchievements.length === 0 &&
                        unearnedAchievements.length === 0
                    "
                    class="flex flex-col items-center gap-3 rounded-xl border border-dashed py-12 text-center"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded-full bg-muted"
                    >
                        <Trophy class="size-6 text-muted-foreground" />
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium">No achievements yet</p>
                        <p class="text-xs text-muted-foreground">
                            Keep using LanCore to unlock your first achievement.
                        </p>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
