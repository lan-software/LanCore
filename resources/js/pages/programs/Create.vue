<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { store } from '@/actions/App/Domain/Program/Http/Controllers/ProgramController';
import { create as programCreate } from '@/actions/App/Domain/Program/Http/Controllers/ProgramController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as programsRoute } from '@/routes/programs';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    events: {
        id: number;
        name: string;
        primary_program_id: number | null;
        primary_program: { id: number; name: string } | null;
    }[];
    selectedEventId?: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: programsRoute().url },
    { title: 'Programs', href: programsRoute().url },
    { title: 'Create', href: programCreate().url },
];

const selectedEventId = ref<string>(
    props.selectedEventId ? String(props.selectedEventId) : '',
);
const isPrimary = ref(false);
const showPrimaryConfirmDialog = ref(false);

const selectedEvent = computed(() =>
    props.events.find((e) => String(e.id) === selectedEventId.value),
);

function onPrimaryToggle(checked: boolean) {
    if (checked && selectedEvent.value?.primary_program) {
        showPrimaryConfirmDialog.value = true;
    } else {
        isPrimary.value = checked;
    }
}

function confirmReplacePrimary() {
    isPrimary.value = true;
    showPrimaryConfirmDialog.value = false;
}

function cancelReplacePrimary() {
    isPrimary.value = false;
    showPrimaryConfirmDialog.value = false;
}

interface NewTimeSlot {
    name: string;
    description: string;
    starts_at: string;
    visibility: 'public' | 'internal' | 'private';
    sort_order: number;
}

const timeSlots = ref<NewTimeSlot[]>([]);

function addTimeSlot() {
    timeSlots.value.push({
        name: '',
        description: '',
        starts_at: '',
        visibility: 'public',
        sort_order: timeSlots.value.length,
    });
}

function removeTimeSlot(index: number) {
    timeSlots.value.splice(index, 1);
    timeSlots.value.forEach((slot, i) => (slot.sort_order = i));
}
</script>

<template>
    <Head title="Create Program" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="programsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Programs
                </Link>
            </div>

            <Form
                v-bind="store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Program Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Program Information"
                        description="Provide the basic details for this program"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="Program name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="Short description of the program…"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="visibility">Visibility</Label>
                            <Select name="visibility" default-value="public">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select visibility"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="public"
                                        >Public</SelectItem
                                    >
                                    <SelectItem value="internal"
                                        >Internal</SelectItem
                                    >
                                    <SelectItem value="private"
                                        >Private</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.visibility" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="event_id">Event</Label>
                            <Select name="event_id" v-model="selectedEventId">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select an event"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="event in events"
                                        :key="event.id"
                                        :value="String(event.id)"
                                    >
                                        {{ event.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.event_id" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_primary"
                            name="is_primary"
                            :value="1"
                            :model-value="isPrimary"
                            @update:model-value="onPrimaryToggle"
                        />
                        <Label for="is_primary" class="cursor-pointer"
                            >Mark as primary program for the event</Label
                        >
                    </div>
                </div>

                <!-- Time Slots -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Time Slots"
                        description="Define the schedule for this program"
                    />

                    <div
                        v-for="(slot, index) in timeSlots"
                        :key="index"
                        class="space-y-3 rounded-lg border p-4"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium"
                                >Time Slot {{ index + 1 }}</span
                            >
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="removeTimeSlot(index)"
                            >
                                <Trash2 class="size-4 text-destructive" />
                            </Button>
                        </div>

                        <input
                            type="hidden"
                            :name="`time_slots[${index}][sort_order]`"
                            :value="slot.sort_order"
                        />

                        <div class="grid gap-2">
                            <Label :for="`time_slots_${index}_name`"
                                >Name</Label
                            >
                            <Input
                                :id="`time_slots_${index}_name`"
                                :name="`time_slots[${index}][name]`"
                                v-model="slot.name"
                                required
                                placeholder="Time slot name"
                            />
                            <InputError
                                :message="errors[`time_slots.${index}.name`]"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`time_slots_${index}_description`"
                                >Description</Label
                            >
                            <Textarea
                                :id="`time_slots_${index}_description`"
                                :name="`time_slots[${index}][description]`"
                                v-model="slot.description"
                                rows="2"
                                placeholder="Short description…"
                            />
                            <InputError
                                :message="
                                    errors[`time_slots.${index}.description`]
                                "
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label :for="`time_slots_${index}_starts_at`"
                                    >Start Time</Label
                                >
                                <Input
                                    :id="`time_slots_${index}_starts_at`"
                                    type="datetime-local"
                                    :name="`time_slots[${index}][starts_at]`"
                                    v-model="slot.starts_at"
                                    required
                                />
                                <InputError
                                    :message="
                                        errors[`time_slots.${index}.starts_at`]
                                    "
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`time_slots_${index}_visibility`"
                                    >Visibility</Label
                                >
                                <Select
                                    :name="`time_slots[${index}][visibility]`"
                                    v-model="slot.visibility"
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="public"
                                            >Public</SelectItem
                                        >
                                        <SelectItem value="internal"
                                            >Internal</SelectItem
                                        >
                                        <SelectItem value="private"
                                            >Private</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <InputError
                                    :message="
                                        errors[`time_slots.${index}.visibility`]
                                    "
                                />
                            </div>
                        </div>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        @click="addTimeSlot"
                    >
                        <Plus class="size-4" />
                        Add Time Slot
                    </Button>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create Program' }}
                    </Button>
                </div>
            </Form>
        </div>

        <!-- Primary program replacement confirmation dialog -->
        <Dialog v-model:open="showPrimaryConfirmDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Replace Primary Program?</DialogTitle>
                    <DialogDescription>
                        The event "{{ selectedEvent?.name }}" already has "{{
                            selectedEvent?.primary_program?.name
                        }}" as its primary program. This will replace it with
                        the new program.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelReplacePrimary">
                        Cancel
                    </Button>
                    <Button @click="confirmReplacePrimary">
                        Replace Primary Program
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
