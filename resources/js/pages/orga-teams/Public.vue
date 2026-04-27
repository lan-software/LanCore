<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import OrgChart from '@/components/orga-team/OrgChart.vue';
import PublicTopbar from '@/components/PublicTopbar.vue';
import EventLayout from '@/layouts/event/EventLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Person = {
    id: number;
    username: string | null;
    profile_emoji: string | null;
    avatar_url: string;
};

type SubTeam = {
    id: number;
    name: string;
    description: string | null;
    emoji: string | null;
    color: string | null;
    leader: Person | null;
    deputies: Person[];
    members: Person[];
};

type OrgaTeam = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    organizer: Person | null;
    deputies: Person[];
    sub_teams: SubTeam[];
};

const props = defineProps<{
    event: {
        id: number;
        name: string;
        start_date: string | null;
        end_date: string | null;
    };
    orgaTeam: OrgaTeam;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Home', href: '/' },
    { title: props.event.name, href: `/events/${props.event.id}/public` },
    { title: 'Orga-Team', href: '#' },
]);
</script>

<template>
    <Head :title="`${orgaTeam.name} — ${event.name}`" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <PublicTopbar />

        <div class="border-b bg-muted/30">
            <div class="mx-auto max-w-5xl px-4 py-2 sm:px-6">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>

        <main class="flex-1 pb-12">
            <EventLayout>
                <div class="space-y-6">
                    <Link
                        :href="`/events/${event.id}/public`"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="size-4" />
                        {{ event.name }}
                    </Link>

                    <header>
                        <p
                            class="text-sm tracking-wider text-muted-foreground uppercase"
                        >
                            Orga-Team
                        </p>
                        <h1 class="mt-2 text-4xl font-bold tracking-tight">
                            {{ orgaTeam.name }}
                        </h1>
                    </header>

                    <OrgChart
                        :orga-team="orgaTeam"
                        organizer-label="Organizer"
                        deputy-label="Deputies"
                        leader-label="Leader"
                        fallback-leader-label="Fallback Leaders"
                        member-label="Members"
                    />
                </div>
            </EventLayout>
        </main>

        <AppFooter />
    </div>
</template>
