<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';

interface SeatedUser {
    username: string;
    profile_emoji: string | null;
    short_bio: string | null;
    avatar_url: string;
    banner_url: string | null;
}

const props = defineProps<{
    seatedUser: SeatedUser;
    anchorRect: DOMRect;
}>();

const CARD_WIDTH = 288; // matches w-72 below
const GAP = 8;

const viewport = ref({
    width: typeof window !== 'undefined' ? window.innerWidth : 1024,
    height: typeof window !== 'undefined' ? window.innerHeight : 768,
});
const cardEl = ref<HTMLDivElement | null>(null);
const cardHeight = ref(0);

function updateViewport(): void {
    viewport.value = { width: window.innerWidth, height: window.innerHeight };
}

function measureCard(): void {
    if (cardEl.value) {
        cardHeight.value = cardEl.value.offsetHeight;
    }
}

onMounted(() => {
    window.addEventListener('resize', updateViewport);
    requestAnimationFrame(measureCard);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateViewport);
});

/**
 * Position the card above the seat by default, flipping below when the
 * card would clip the top of the viewport. Horizontal centre over the seat,
 * clamped within the viewport so we never spill off-screen.
 */
const position = computed(() => {
    const rect = props.anchorRect;
    const seatCenterX = rect.left + rect.width / 2;

    let left = seatCenterX - CARD_WIDTH / 2;
    left = Math.max(
        GAP,
        Math.min(left, viewport.value.width - CARD_WIDTH - GAP),
    );

    const measuredHeight = cardHeight.value || 200;
    const aboveTop = rect.top - measuredHeight - GAP;
    const belowTop = rect.bottom + GAP;

    const top =
        aboveTop >= GAP
            ? aboveTop
            : Math.min(belowTop, viewport.value.height - measuredHeight - GAP);

    return {
        top: `${top}px`,
        left: `${left}px`,
        width: `${CARD_WIDTH}px`,
    };
});

const initials = computed(() => getInitials(props.seatedUser.username ?? '?'));
</script>

<template>
    <Teleport to="body">
        <div
            ref="cardEl"
            class="pointer-events-none fixed z-[60] overflow-hidden rounded-xl border bg-card text-card-foreground shadow-lg"
            :style="position"
        >
            <div
                class="relative h-16 w-full bg-gradient-to-br from-muted to-muted/40"
            >
                <img
                    v-if="seatedUser.banner_url"
                    :src="seatedUser.banner_url"
                    alt=""
                    class="h-full w-full object-cover"
                    draggable="false"
                />
            </div>
            <div class="px-4 pb-4">
                <Avatar class="-mt-7 size-14 border-2 border-card shadow-sm">
                    <AvatarImage
                        :src="seatedUser.avatar_url"
                        :alt="seatedUser.username"
                    />
                    <AvatarFallback>{{ initials }}</AvatarFallback>
                </Avatar>
                <div class="mt-2 flex items-center gap-1.5">
                    <span v-if="seatedUser.profile_emoji" aria-hidden="true">{{
                        seatedUser.profile_emoji
                    }}</span>
                    <span class="truncate text-base font-semibold"
                        >@{{ seatedUser.username }}</span
                    >
                </div>
                <p
                    v-if="seatedUser.short_bio"
                    class="mt-1.5 line-clamp-2 text-sm text-muted-foreground"
                >
                    {{ seatedUser.short_bio }}
                </p>
            </div>
        </div>
    </Teleport>
</template>
