<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';
import { show as profileShow } from '@/routes/public-profile';

type Person = {
    id: number;
    username: string | null;
    profile_emoji: string | null;
    avatar_url: string;
};

const props = withDefaults(
    defineProps<{
        person: Person;
        roleLabel?: string | null;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        roleLabel: null,
        size: 'md',
    },
);

const sizeClasses = {
    sm: { avatar: 'size-8', name: 'text-sm', role: 'text-xs' },
    md: { avatar: 'size-10', name: 'text-sm', role: 'text-xs' },
    lg: { avatar: 'size-14', name: 'text-base font-semibold', role: 'text-sm' },
}[props.size];

function initials(p: Person): string {
    return (p.username ?? '?').slice(0, 2).toUpperCase();
}
</script>

<template>
    <component
        :is="person.username ? Link : 'div'"
        :href="
            person.username
                ? profileShow({ username: person.username }).url
                : undefined
        "
        class="group flex items-center gap-3 rounded-lg border bg-card p-3 transition-colors hover:bg-muted/50"
    >
        <Avatar :class="sizeClasses.avatar">
            <AvatarImage :src="person.avatar_url" :alt="person.username ?? ''" />
            <AvatarFallback>{{ initials(person) }}</AvatarFallback>
        </Avatar>
        <div class="min-w-0 flex-1">
            <div :class="['truncate', sizeClasses.name]">
                <span v-if="person.profile_emoji" class="mr-1">{{
                    person.profile_emoji
                }}</span>
                <span v-if="person.username">@{{ person.username }}</span>
                <span v-else class="italic text-muted-foreground"
                    >Player #{{ person.id }}</span
                >
            </div>
            <div
                v-if="roleLabel"
                :class="['truncate text-muted-foreground', sizeClasses.role]"
            >
                {{ roleLabel }}
            </div>
        </div>
    </component>
</template>
