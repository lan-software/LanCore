<script setup lang="ts">
import type { TabsRootEmits, TabsRootProps } from 'reka-ui';
import { TabsRoot, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<TabsRootProps & { class?: string }>();
const emits = defineEmits<TabsRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <TabsRoot
        v-bind="forwarded"
        :class="cn('flex flex-col gap-4', props.class)"
    >
        <slot />
    </TabsRoot>
</template>
