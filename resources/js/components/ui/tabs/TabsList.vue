<script setup lang="ts">
import type { TabsListProps } from 'reka-ui';
import { TabsList } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<TabsListProps & { class?: string }>();
</script>

<template>
    <TabsList
        v-bind="props"
        :class="
            cn(
                'inline-flex h-10 items-center justify-start gap-1 rounded-md border border-sidebar-border/70 bg-muted/40 p-1 text-muted-foreground dark:border-sidebar-border',
                props.class,
            )
        "
    >
        <slot />
    </TabsList>
</template>
