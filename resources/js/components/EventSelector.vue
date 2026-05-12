<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Calendar, Check } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
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
const sidebar = props.sidebar ? useSidebar() : null;

const isCollapsed = computed(
    () => sidebar?.state.value === 'collapsed' && !sidebar?.isMobile.value,
);

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

const selectedEventName = computed(() => {
    const id = context.value?.selectedEventId;

    if (!id) {
        return null;
    }

    return context.value?.events.find((e) => e.id === id)?.name ?? null;
});

const tooltipLabel = computed(() =>
    selectedEventName.value
        ? `${props.label}: ${selectedEventName.value}`
        : `${props.label}: All Events`,
);

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
                    <DropdownMenu v-if="isCollapsed">
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton
                                :tooltip="tooltipLabel"
                                :is-active="!!selectedEventName"
                                class="justify-center"
                                data-test="event-selector-collapsed-trigger"
                            >
                                <Calendar />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            side="right"
                            align="start"
                            :side-offset="4"
                            class="min-w-56"
                        >
                            <DropdownMenuLabel>{{ label }}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                class="justify-between"
                                @click="onSelect('all')"
                            >
                                <span>All Events</span>
                                <Check
                                    v-if="selectedValue === 'all'"
                                    class="size-4"
                                />
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-for="event in context.events"
                                :key="event.id"
                                class="justify-between"
                                @click="onSelect(String(event.id))"
                            >
                                <span>{{ event.name }}</span>
                                <Check
                                    v-if="selectedValue === String(event.id)"
                                    class="size-4"
                                />
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Select
                        v-else
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
