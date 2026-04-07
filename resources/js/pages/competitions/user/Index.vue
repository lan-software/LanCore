<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    ExternalLink,
    Swords,
} from 'lucide-vue-next';
import UserCompetitionController from '@/actions/App/Domain/Competition/Http/Controllers/UserCompetitionController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import EventSelector from '@/components/EventSelector.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as myCompetitionsRoute } from '@/routes/my-competitions';
import type { BreadcrumbItem } from '@/types';
import type { Competition } from '@/types/domain';

interface PaginatedCompetitions {
    data: Competition[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    competitions: PaginatedCompetitions;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Competitions', href: myCompetitionsRoute().url },
];

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    registration_open:
        'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    registration_closed:
        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    running:
        'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
    finished: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    archived: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
};
</script>

<template>
    <Head title="My Competitions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <EventSelector variant="my" :sidebar="false" />
            <div
                v-if="competitions.data.length"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <Link
                    v-for="comp in competitions.data"
                    :key="comp.id"
                    :href="UserCompetitionController.show(comp.id).url"
                    class="group rounded-xl border border-sidebar-border/70 p-4 transition hover:shadow-md dark:border-sidebar-border"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <Swords class="size-4 text-muted-foreground" />
                        <h3 class="font-semibold group-hover:text-primary">
                            {{ comp.name }}
                        </h3>
                    </div>
                    <div class="mb-2 flex items-center gap-2">
                        <Badge
                            variant="outline"
                            :class="statusColors[comp.status] ?? ''"
                        >
                            {{
                                comp.status
                                    .replace(/_/g, ' ')
                                    .replace(/\b\w/g, (c: string) =>
                                        c.toUpperCase(),
                                    )
                            }}
                        </Badge>
                        <span
                            v-if="comp.game"
                            class="text-xs text-muted-foreground"
                        >
                            {{ comp.game.name }}
                        </span>
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ comp.teams_count ?? 0 }} teams
                        <template v-if="comp.starts_at">
                            &middot;
                            {{ new Date(comp.starts_at).toLocaleDateString() }}
                        </template>
                    </div>
                </Link>
            </div>

            <div
                v-else
                class="flex flex-1 items-center justify-center text-muted-foreground"
            >
                You are not participating in any competitions yet.
            </div>

            <!-- Pagination -->
            <div
                v-if="competitions.last_page > 1"
                class="flex items-center justify-center gap-1"
            >
                <Button
                    v-for="link in competitions.links"
                    :key="link.label"
                    variant="outline"
                    size="sm"
                    :disabled="!link.url || link.active"
                    as-child
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                    />
                    <span v-else v-html="link.label" />
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
