<script setup lang="ts">
/**
 * EventLayout
 * Event-bound page layout providing a right content slot for event-scoped widgets
 * (Orga-Team card, future: Sponsors, Tournaments, etc.).
 *
 * - Default slot = main page content.
 * - "right" slot = RightContentArea content (event-bound widgets).
 * - On viewports below `lg`, the right slot stacks BELOW the main content.
 *
 * The layout is presentation-only; embedding pages keep their own topbar/footer.
 *
 * @see docs/mil-std-498/SRS.md OT-F-009
 */
defineSlots<{
    default(): unknown;
    right?(): unknown;
}>();

defineProps<{
    hasRightContent?: boolean;
}>();
</script>

<template>
    <div
        :class="[
            'mx-auto grid w-full max-w-7xl gap-6 px-6 py-12',
            hasRightContent && $slots.right
                ? 'lg:grid-cols-[minmax(0,1fr)_280px]'
                : '',
        ]"
    >
        <!-- Main content (left on lg+, top on mobile) -->
        <main class="order-1 min-w-0">
            <slot />
        </main>

        <!-- Right content area (below on mobile, beside-right on lg+) -->
        <aside
            v-if="hasRightContent && $slots.right"
            class="order-2 space-y-6 lg:sticky lg:top-6 lg:self-start"
        >
            <slot name="right" />
        </aside>
    </div>
</template>
