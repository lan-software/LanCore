<script setup lang="ts">
/**
 * Renders two side-by-side preview panels (light + dark) that show how
 * the picked palette affects representative UI: a sidebar slice, a card,
 * primary/secondary buttons, an input, badges, and muted text.
 *
 * Each panel composes the platform's default light/dark baseline plus
 * the user's overrides into a single inline-style map of CSS custom
 * properties. Inline styles win over inherited cascade, so the panel
 * renders correctly regardless of whether the document `<html>` carries
 * the `dark` class — i.e. the editor previews both modes accurately
 * even when the user's personal appearance is `system` or `dark`.
 *
 * @see docs/mil-std-498/SDD.md §5.11
 */
import {
    BellRing,
    LayoutDashboard,
    Search,
    Settings,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DARK_BASELINE,
    LIGHT_BASELINE,
} from '@/components/theme/themeBaselines';

const props = defineProps<{
    lightConfig: Record<string, string>;
    darkConfig: Record<string, string>;
}>();

function sanitize(overrides: Record<string, string>): Record<string, string> {
    const out: Record<string, string> = {};

    for (const [k, v] of Object.entries(overrides)) {
        if (!/^--[a-z][a-z0-9-]*$/.test(k)) {
            continue;
        }

        const trimmed = v.trim();

        if (!trimmed || /[;}<>]/.test(trimmed)) {
            continue;
        }

        out[k] = trimmed;
    }

    return out;
}

const lightStyle = computed<Record<string, string>>(() => ({
    ...LIGHT_BASELINE,
    ...sanitize(props.lightConfig),
}));

const darkStyle = computed<Record<string, string>>(() => ({
    ...DARK_BASELINE,
    ...sanitize(props.darkConfig),
}));

const navItems = [
    { icon: LayoutDashboard, label: 'Dashboard' },
    { icon: Users, label: 'Attendees' },
    { icon: BellRing, label: 'Announcements' },
    { icon: Settings, label: 'Settings' },
];

const panels = computed(() => [
    { label: 'Light', style: lightStyle.value },
    { label: 'Dark', style: darkStyle.value },
]);
</script>

<template>
    <div class="space-y-4">
        <div
            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Preview
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <div
                v-for="panel in panels"
                :key="panel.label"
                class="overflow-hidden rounded-xl border border-border"
            >
                <div
                    class="bg-muted/40 px-3 py-2 text-xs font-medium text-muted-foreground"
                >
                    {{ panel.label }}
                </div>
                <div
                    class="grid grid-cols-[10rem_1fr] bg-background text-foreground"
                    :style="panel.style"
                >
                    <aside
                        class="flex flex-col gap-1 border-r border-sidebar-border bg-sidebar p-3 text-sidebar-foreground"
                    >
                        <div
                            class="px-2 py-1.5 text-[10px] font-semibold tracking-wide uppercase"
                            style="
                                color: var(--sidebar-foreground);
                                opacity: 0.6;
                            "
                        >
                            LanCore
                        </div>
                        <div
                            v-for="item in navItems"
                            :key="item.label"
                            :class="[
                                'flex items-center gap-2 rounded-md px-2 py-1.5 text-xs',
                                item.label === 'Dashboard'
                                    ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                    : '',
                            ]"
                            :style="
                                item.label === 'Dashboard'
                                    ? {}
                                    : { color: 'var(--sidebar-foreground)' }
                            "
                        >
                            <component :is="item.icon" class="size-3.5" />
                            {{ item.label }}
                        </div>
                    </aside>
                    <div class="flex flex-col gap-3 p-4">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex flex-1 items-center gap-2 rounded-md border border-input bg-background px-2 py-1 text-xs text-muted-foreground"
                            >
                                <Search class="size-3.5" />
                                <span>Search…</span>
                            </div>
                            <button
                                type="button"
                                class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground"
                            >
                                New
                            </button>
                            <button
                                type="button"
                                class="rounded-md bg-secondary px-3 py-1.5 text-xs font-medium text-secondary-foreground"
                            >
                                Cancel
                            </button>
                        </div>
                        <div
                            class="rounded-lg border border-border bg-card p-3 text-card-foreground shadow-sm"
                        >
                            <div class="mb-1 text-sm font-semibold">
                                Welcome back
                            </div>
                            <div class="mb-3 text-xs text-muted-foreground">
                                The next event kicks off in 3 days.
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    class="rounded-full bg-accent px-2 py-0.5 text-[10px] font-medium text-accent-foreground"
                                >
                                    Accent
                                </span>
                                <span
                                    class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-medium text-primary-foreground"
                                >
                                    Primary
                                </span>
                                <span
                                    class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground"
                                >
                                    Muted
                                </span>
                            </div>
                        </div>
                        <div class="text-[10px] text-muted-foreground">
                            Focus ring sample:
                            <button
                                type="button"
                                class="ml-1 inline-block rounded-md border border-input bg-background px-2 py-0.5 text-[10px] outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                Tab to focus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
