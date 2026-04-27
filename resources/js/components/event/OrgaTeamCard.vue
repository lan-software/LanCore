<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Users } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';
import { show as orgaTeamShow } from '@/routes/events/orga-team';

type Person = {
    id: number;
    username: string | null;
    profile_emoji: string | null;
    avatar_url: string;
};

type OrgaTeam = {
    id: number;
    name: string;
    organizer: Person | null;
    deputies: Person[];
    sub_teams: { id: number }[];
};

const props = defineProps<{
    eventId: number;
    orgaTeam: OrgaTeam;
    title?: string;
    organizerLabel?: string;
    viewAllLabel?: string;
}>();

const previewDeputies = computed(() => props.orgaTeam.deputies.slice(0, 2));

function initials(p: Person): string {
    return (p.username ?? '?').slice(0, 2).toUpperCase();
}
</script>

<template>
    <section class="rounded-xl border bg-card p-5 shadow-sm">
        <header class="mb-4 flex items-center gap-2">
            <Users class="size-4 text-muted-foreground" />
            <h2 class="text-sm font-semibold tracking-wide uppercase">
                {{ title ?? 'Orga-Team' }}
            </h2>
        </header>

        <p class="mb-4 text-sm font-medium">{{ orgaTeam.name }}</p>

        <div v-if="orgaTeam.organizer" class="mb-3">
            <p class="mb-1 text-[10px] tracking-wider text-muted-foreground uppercase">
                {{ organizerLabel ?? 'Organizer' }}
            </p>
            <div class="flex items-center gap-2">
                <Avatar class="size-9">
                    <AvatarImage
                        :src="orgaTeam.organizer.avatar_url"
                        :alt="orgaTeam.organizer.username ?? ''"
                    />
                    <AvatarFallback>{{
                        initials(orgaTeam.organizer)
                    }}</AvatarFallback>
                </Avatar>
                <div class="min-w-0">
                    <p class="truncate text-sm">
                        <span v-if="orgaTeam.organizer.profile_emoji" class="mr-1">{{
                            orgaTeam.organizer.profile_emoji
                        }}</span>
                        <span v-if="orgaTeam.organizer.username">
                            @{{ orgaTeam.organizer.username }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div v-if="previewDeputies.length > 0" class="mb-3 flex -space-x-2">
            <Avatar
                v-for="d in previewDeputies"
                :key="d.id"
                class="size-7 border-2 border-card"
            >
                <AvatarImage :src="d.avatar_url" :alt="d.username ?? ''" />
                <AvatarFallback>{{ initials(d) }}</AvatarFallback>
            </Avatar>
        </div>

        <p class="mb-4 text-xs text-muted-foreground">
            {{ orgaTeam.sub_teams.length }} sub-teams
        </p>

        <Link
            :href="orgaTeamShow({ event: eventId }).url"
            class="text-sm font-medium text-primary hover:underline"
        >
            {{ viewAllLabel ?? 'View full team →' }}
        </Link>
    </section>
</template>
