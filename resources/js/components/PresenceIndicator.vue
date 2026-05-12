<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type PresenceStatus = 'active' | 'idle' | 'offline';

type Props = {
    status: PresenceStatus;
    size?: 'sm' | 'md';
    withLabel?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    withLabel: false,
});

const { t } = useI18n();

const label = computed(() => t(`presence.status.${props.status}`));

const dotSizeClass = computed(() =>
    props.size === 'sm' ? 'h-2 w-2' : 'h-3 w-3',
);
const iconSizeClass = computed(() =>
    props.size === 'sm' ? 'h-1.5 w-1.5' : 'h-2 w-2',
);

const colorClass = computed(() => {
    switch (props.status) {
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
                v-if="status === 'active'"
                :class="[iconSizeClass, 'stroke-[3] text-white']"
                aria-hidden="true"
            />
        </span>
        <span v-if="withLabel" class="text-sm text-muted-foreground">{{
            label
        }}</span>
    </span>
</template>
