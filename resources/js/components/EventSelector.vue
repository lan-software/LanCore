<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Calendar } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { EventContext } from '@/types';

type Variant = 'admin' | 'my';

const props = withDefaults(
    defineProps<{
        variant?: Variant;
        sidebar?: boolean;
        label?: string;
    }>(),
    {
        variant: 'admin',
        sidebar: true,
        label: 'Event Context',
    },
);

const page = usePage();

const context = computed(() => {
    if (props.variant === 'my') {
        const c = page.props.myEventContext as {
            selectedEventId: number | null;
            events: { id: number; name: string }[];
        } | null;

        return c;
    }

    return page.props.eventContext as EventContext | null;
});

const endpoint = computed(() =>
    props.variant === 'my' ? '/my-event-context' : '/event-context',
);

const selectedValue = computed(() => {
    const id = context.value?.selectedEventId;

    return id ? String(id) : 'all';
});

function onSelect(value: string) {
    if (value === 'all') {
        router.delete(endpoint.value, {
            preserveScroll: true,
            preserveState: true,
        });
    } else {
        router.post(
            endpoint.value,
            { event_id: Number(value) },
            { preserveScroll: true, preserveState: true },
        );
    }
}
</script>

<template>
    <SidebarGroup v-if="sidebar && context">
        <SidebarGroupLabel>
            <Calendar class="size-4" />
            <span class="ml-1">{{ label }}</span>
        </SidebarGroupLabel>
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem>
                    <Select
                        :model-value="selectedValue"
                        @update:model-value="onSelect"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="All Events" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Events</SelectItem>
                            <SelectItem
                                v-for="event in context.events"
                                :key="event.id"
                                :value="String(event.id)"
                            >
                                {{ event.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
    <div
        v-else-if="context && context.events.length > 0"
        class="flex items-center gap-2"
    >
        <Calendar class="size-4 text-muted-foreground" />
        <Select :model-value="selectedValue" @update:model-value="onSelect">
            <SelectTrigger class="w-full max-w-xs">
                <SelectValue placeholder="All Events" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">All Events</SelectItem>
                <SelectItem
                    v-for="event in context.events"
                    :key="event.id"
                    :value="String(event.id)"
                >
                    {{ event.name }}
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
