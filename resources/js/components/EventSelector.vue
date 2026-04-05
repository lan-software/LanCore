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

const page = usePage();

const eventContext = computed(
    () => page.props.eventContext as EventContext | null,
);

const selectedValue = computed(() => {
    const id = eventContext.value?.selectedEventId;

    return id ? String(id) : 'all';
});

function onSelect(value: string) {
    if (value === 'all') {
        router.delete('/event-context', {
            preserveScroll: true,
            preserveState: true,
        });
    } else {
        router.post(
            '/event-context',
            { event_id: Number(value) },
            { preserveScroll: true, preserveState: true },
        );
    }
}
</script>

<template>
    <SidebarGroup v-if="eventContext">
        <SidebarGroupLabel>
            <Calendar class="size-4" />
            <span class="ml-1">Event Context</span>
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
                                v-for="event in eventContext.events"
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
</template>
