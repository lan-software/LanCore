<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils';
import { show as profileShow } from '@/routes/public-profile';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
    hideDetailsOnMobile?: boolean;
    linkUsername?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    hideDetailsOnMobile: false,
    linkUsername: false,
});

const { getInitials } = useInitials();

const displayName = computed(() => props.user.username ?? props.user.name);

const profileHref = computed(() =>
    props.user.username
        ? profileShow({ username: props.user.username }).url
        : null,
);

const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage
            v-if="showAvatar"
            :src="user.avatar!"
            :alt="displayName"
        />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(displayName) }}
        </AvatarFallback>
    </Avatar>

    <div
        :class="
            cn(
                'grid flex-1 text-left text-sm leading-tight',
                props.hideDetailsOnMobile && 'hidden sm:grid',
            )
        "
    >
        <Link
            v-if="linkUsername && profileHref"
            :href="profileHref"
            class="truncate font-medium hover:underline"
        >
            {{ displayName }}
        </Link>
        <span v-else class="truncate font-medium">{{ displayName }}</span>
        <span
            v-if="showEmail"
            class="truncate text-xs text-muted-foreground"
            >{{ user.email }}</span
        >
    </div>
</template>
