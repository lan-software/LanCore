<script setup lang="ts">
import PersonCard from '@/components/orga-team/PersonCard.vue';
import SubTeamGroup from '@/components/orga-team/SubTeamGroup.vue';

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

defineProps<{
    orgaTeam: OrgaTeam;
    organizerLabel: string;
    deputyLabel: string;
    leaderLabel: string;
    fallbackLeaderLabel: string;
    memberLabel: string;
}>();
</script>

<template>
    <div class="flex flex-col items-stretch gap-10">
        <!-- Organizer + Deputies -->
        <section class="space-y-4">
            <h2 class="text-2xl font-bold tracking-tight">{{ orgaTeam.name }}</h2>
            <p
                v-if="orgaTeam.description"
                class="text-muted-foreground"
            >
                {{ orgaTeam.description }}
            </p>

            <div v-if="orgaTeam.organizer" class="space-y-3">
                <p
                    class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                >
                    {{ organizerLabel }}
                </p>
                <div class="max-w-md">
                    <PersonCard :person="orgaTeam.organizer" size="lg" />
                </div>
            </div>

            <div v-if="orgaTeam.deputies.length > 0" class="space-y-3">
                <p
                    class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                >
                    {{ deputyLabel }}
                </p>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <PersonCard
                        v-for="d in orgaTeam.deputies"
                        :key="d.id"
                        :person="d"
                    />
                </div>
            </div>
        </section>

        <!-- Sub-Teams -->
        <section
            v-if="orgaTeam.sub_teams.length > 0"
            class="grid gap-6 lg:grid-cols-2"
        >
            <SubTeamGroup
                v-for="sub in orgaTeam.sub_teams"
                :key="sub.id"
                :sub-team="sub"
                :leader-label="leaderLabel"
                :deputy-label="fallbackLeaderLabel"
                :member-label="memberLabel"
            />
        </section>
    </div>
</template>
