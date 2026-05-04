<script setup lang="ts">
/**
 * Teleports two CSS-variable override `<style>` blocks into the document
 * head — one scoped to `:root` (light), one scoped to `.dark` (dark) — so
 * the active palette Theme overrides the platform's default tokens
 * declared in `resources/css/app.css`.
 *
 * Mounted once at the app shell level in `resources/js/app.ts`.
 *
 * @see docs/mil-std-498/SDD.md §5.11
 */
import { computed } from 'vue';
import { useEventTheme } from '@/composables/useEventTheme';

const { lightConfig, darkConfig } = useEventTheme();

function buildBlock(
    selector: string,
    overrides: Record<string, string>,
): string {
    const declarations = Object.entries(overrides)
        .filter(
            ([key, value]) =>
                /^--[a-z][a-z0-9-]*$/.test(key) && !/[;}<>]/.test(value),
        )
        .map(([key, value]) => `    ${key}: ${value};`)
        .join('\n');

    if (!declarations) {
        return '';
    }

    return `${selector} {\n${declarations}\n}`;
}

const lightCss = computed<string>(() => buildBlock(':root', lightConfig.value));
const darkCss = computed<string>(() => buildBlock('.dark', darkConfig.value));
</script>

<template>
    <slot />
    <Teleport to="head">
        <component :is="'style'" v-if="lightCss" id="event-theme-vars-light">{{
            lightCss
        }}</component>
        <component :is="'style'" v-if="darkCss" id="event-theme-vars-dark">{{
            darkCss
        }}</component>
    </Teleport>
</template>
