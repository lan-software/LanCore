<script setup lang="ts">
import PersonCard from '@/components/orga-team/PersonCard.vue';

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

defineProps<{
    subTeam: SubTeam;
    leaderLabel: string;
    deputyLabel: string;
    memberLabel: string;
}>();
</script>

<template>
    <section
        class="rounded-xl border bg-card p-5 shadow-sm"
        :style="
            subTeam.color
                ? `border-left-width: 4px; border-left-color: ${subTeam.color};`
                : undefined
        "
    >
        <header class="mb-4 flex items-center gap-2">
            <span v-if="subTeam.emoji" class="text-2xl">{{
                subTeam.emoji
            }}</span>
            <h3 class="text-lg font-semibold">{{ subTeam.name }}</h3>
        </header>

        <p
            v-if="subTeam.description"
            class="mb-4 text-sm text-muted-foreground"
        >
            {{ subTeam.description }}
        </p>

        <div v-if="subTeam.leader" class="mb-4">
            <p
                class="mb-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
            >
                {{ leaderLabel }}
            </p>
            <PersonCard :person="subTeam.leader" size="lg" />
        </div>

        <div v-if="subTeam.deputies.length > 0" class="mb-4">
            <p
                class="mb-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
            >
                {{ deputyLabel }}
            </p>
            <div class="grid gap-2 sm:grid-cols-2">
                <PersonCard
                    v-for="d in subTeam.deputies"
                    :key="d.id"
                    :person="d"
                    size="sm"
                />
            </div>
        </div>

        <div v-if="subTeam.members.length > 0">
            <p
                class="mb-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
            >
                {{ memberLabel }}
            </p>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <PersonCard
                    v-for="m in subTeam.members"
                    :key="m.id"
                    :person="m"
                    size="sm"
                />
            </div>
        </div>
    </section>
</template>
