<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type PresenceStatus = 'active' | 'idle' | 'offline';

type Props = {
    status: PresenceStatus;
    size?: 'sm' | 'md';
    withLabel?: boolean;
    /**
     * When true, render a check glyph inside the green dot to signal the
     * user is *currently active in this chat room*, as opposed to just
     * active on LanCore in general (plain green dot). See SRS CHT-F-036.
     */
    inChat?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    withLabel: false,
    inChat: false,
});

const { t } = useI18n();

const label = computed(() => {
    if (props.inChat) {
return t('presence.status.activeInChat');
}

    return t(`presence.status.${props.status}`);
});

// In-chat trumps LanCore presence visually — if a user is in the chat we
// always show the green-with-check dot (they're definitionally active).
const effectiveStatus = computed<PresenceStatus>(() =>
    props.inChat ? 'active' : props.status,
);

const dotSizeClass = computed(() =>
    props.size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4',
);
const iconSizeClass = computed(() =>
    props.size === 'sm' ? 'h-2.5 w-2.5' : 'h-3 w-3',
);

const colorClass = computed(() => {
    switch (effectiveStatus.value) {
        case 'active':
            return 'bg-green-500 dark:bg-green-400';
        case 'idle':
            return 'bg-yellow-500 dark:bg-yellow-400';
        case 'offline':
        default:
            return 'bg-zinc-400 dark:bg-zinc-600';
    }
});
</script>

<template>
    <span class="inline-flex items-center gap-1.5">
        <span
            :class="[
                'inline-flex items-center justify-center rounded-full ring-1 ring-black/5 ring-inset dark:ring-white/10',
                dotSizeClass,
                colorClass,
            ]"
            :aria-label="label"
            role="img"
        >
            <Check
                v-if="inChat"
                :class="[iconSizeClass, 'stroke-[3] text-white']"
                aria-hidden="true"
            />
        </span>
        <span v-if="withLabel" class="text-sm text-muted-foreground">{{
            label
        }}</span>
    </span>
</template>
