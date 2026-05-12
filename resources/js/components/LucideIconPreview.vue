<script setup lang="ts">
// @see GitHub #11 — renders an icon by name (kebab/camel/Pascal). Falls back
// to a "?" badge when the name is empty or unknown.
import { icons as lucideIcons, HelpCircle } from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        name: string | null | undefined;
        /** Size class for both the icon and the fallback badge. */
        sizeClass?: string;
        /** Add a `title` attribute exposing the icon name on hover. */
        withTooltip?: boolean;
    }>(),
    {
        sizeClass: 'size-5',
        withTooltip: false,
    },
);

const trimmed = computed<string>(() => (props.name ?? '').trim());

const pascalName = computed<string>(() => {
    if (trimmed.value === '') return '';
    // Accept inputs in any of: trophy, Trophy, trophy-icon, trophy_icon, trophyIcon
    return trimmed.value
        .split(/[-_\s]+/)
        .filter((part) => part.length > 0)
        .map(
            (part) =>
                part.charAt(0).toUpperCase() + part.slice(1).toLowerCase(),
        )
        .join('');
});

const resolved = computed<Component | null>(() => {
    if (pascalName.value === '') return null;
    const lookup = lucideIcons as unknown as Record<string, Component>;
    return lookup[pascalName.value] ?? null;
});

const tooltip = computed<string | undefined>(() => {
    if (!props.withTooltip) return undefined;
    if (trimmed.value === '') return 'No icon';
    return resolved.value ? trimmed.value : `Unknown icon: ${trimmed.value}`;
});
</script>

<template>
    <component
        :is="resolved"
        v-if="resolved"
        :class="sizeClass"
        :aria-label="trimmed || undefined"
        :title="tooltip"
    />
    <span
        v-else
        :class="[
            sizeClass,
            'inline-flex items-center justify-center rounded-sm bg-muted/60 text-muted-foreground ring-1 ring-border ring-inset',
        ]"
        :title="tooltip"
        :aria-label="trimmed ? `Unknown icon: ${trimmed}` : 'No icon set'"
    >
        <HelpCircle class="size-[60%]" aria-hidden="true" />
    </span>
</template>
