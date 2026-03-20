<script setup lang="ts">
import { edit as programEdit, update, destroy } from '@/actions/App/Domain/Program/Http/Controllers/ProgramController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as programsRoute } from '@/routes/programs'
import type { BreadcrumbItem } from '@/types'
import type { Program, Sponsor, TimeSlot } from '@/types/domain'
import { Form, Head, Link, router } from '@inertiajs/vue3'
import { Plus, Trash2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'

const props = defineProps<{
    program: Program & { time_slots: TimeSlot[] }
    isPrimary: boolean
    events: { id: number; name: string; primary_program_id: number | null; primary_program: { id: number; name: string } | null }[]
    sponsors: Sponsor[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: programsRoute().url },
    { title: 'Programs', href: programsRoute().url },
    { title: props.program.name, href: programEdit(props.program.id).url },
]

function formatDateTimeLocal(dateString: string): string {
    const date = new Date(dateString)
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const showDeleteDialog = ref(false)
const showPrimaryConfirmDialog = ref(false)
const isPrimaryChecked = ref(props.isPrimary)

const currentEvent = computed(() =>
    props.events.find((e) => e.id === props.program.event_id),
)

function onPrimaryToggle(checked: boolean | 'indeterminate') {
    if (checked === 'indeterminate') return
    if (checked && currentEvent.value?.primary_program && currentEvent.value.primary_program.id !== props.program.id) {
        showPrimaryConfirmDialog.value = true
    } else {
        isPrimaryChecked.value = checked
    }
}

function confirmReplacePrimary() {
    isPrimaryChecked.value = true
    showPrimaryConfirmDialog.value = false
}

function cancelReplacePrimary() {
    isPrimaryChecked.value = false
    showPrimaryConfirmDialog.value = false
}

interface EditableTimeSlot {
    id?: number
    name: string
    description: string
    starts_at: string
    visibility: 'public' | 'internal' | 'private'
    sort_order: number
    sponsor_ids: number[]
}

const programSponsorIds = ref<number[]>(
    props.program.sponsors?.map((s) => s.id) ?? [],
)

const timeSlots = ref<EditableTimeSlot[]>(
    props.program.time_slots.map((slot, i) => ({
        id: slot.id,
        name: slot.name,
        description: slot.description ?? '',
        starts_at: formatDateTimeLocal(slot.starts_at),
        visibility: slot.visibility,
        sort_order: i,
        sponsor_ids: slot.sponsors?.map((s) => s.id) ?? [],
    })),
)

function addTimeSlot() {
    timeSlots.value.push({
        name: '',
        description: '',
        starts_at: '',
        visibility: 'public',
        sort_order: timeSlots.value.length,
        sponsor_ids: [],
    })
}

function removeTimeSlot(index: number) {
    timeSlots.value.splice(index, 1)
    timeSlots.value.forEach((slot, i) => (slot.sort_order = i))
}

function executeDelete() {
    router.delete(destroy(props.program.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false
        },
    })
}

function toggleProgramSponsor(sponsorId: number, checked: boolean | 'indeterminate') {
    if (checked === true) {
        programSponsorIds.value.push(sponsorId)
    } else {
        programSponsorIds.value = programSponsorIds.value.filter((id) => id !== sponsorId)
    }
}

function toggleSlotSponsor(slotIndex: number, sponsorId: number, checked: boolean | 'indeterminate') {
    const ids = timeSlots.value[slotIndex].sponsor_ids
    if (checked === true) {
        ids.push(sponsorId)
    } else {
        timeSlots.value[slotIndex].sponsor_ids = ids.filter((id) => id !== sponsorId)
    }
}
</script>

<template>
    <Head :title="`Edit ${program.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
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
                v-bind="update.form(program.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Program Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Program Information"
                        description="Update the basic details for this program"
                    />
                    <p v-if="programSponsorIds.length > 0" class="text-sm italic text-muted-foreground">
                        presented by {{ sponsors.filter((s) => programSponsorIds.includes(s.id)).map((s) => s.name).join(', ') }}
                    </p>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="program.name"
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
                            :default-value="program.description ?? ''"
                            rows="3"
                            placeholder="Short description of the program…"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="visibility">Visibility</Label>
                        <Select name="visibility" :default-value="program.visibility">
                            <SelectTrigger>
                                <SelectValue placeholder="Select visibility" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="public">Public</SelectItem>
                                <SelectItem value="internal">Internal</SelectItem>
                                <SelectItem value="private">Private</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.visibility" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_primary"
                            name="is_primary"
                            :value="1"
                            :model-value="isPrimaryChecked"
                            @update:model-value="onPrimaryToggle"
                        />
                        <Label for="is_primary" class="cursor-pointer">Mark as primary program for the event</Label>
                    </div>
                </div>

                <!-- Program-Level Sponsors -->
                <div v-if="sponsors.length > 0" class="space-y-4">
                    <Heading
                        variant="small"
                        title="Program Sponsors"
                        description="Select sponsors associated with this entire program"
                    />

                    <div class="grid gap-2">
                        <div v-for="sponsor in sponsors" :key="sponsor.id" class="flex items-center gap-2">
                            <Checkbox
                                :id="`program_sponsor_${sponsor.id}`"
                                :model-value="programSponsorIds.includes(sponsor.id)"
                                @update:model-value="(checked: boolean | 'indeterminate') => toggleProgramSponsor(sponsor.id, checked)"
                            />
                            <input
                                v-if="programSponsorIds.includes(sponsor.id)"
                                type="hidden"
                                name="sponsor_ids[]"
                                :value="sponsor.id"
                            />
                            <Label :for="`program_sponsor_${sponsor.id}`" class="cursor-pointer">
                                {{ sponsor.name }}
                                <span v-if="sponsor.sponsor_level" class="text-xs text-muted-foreground">({{ sponsor.sponsor_level.name }})</span>
                            </Label>
                        </div>
                    </div>
                    <InputError :message="errors.sponsor_ids" />
                </div>

                <!-- Time Slots -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Time Slots"
                        description="Manage the schedule for this program"
                    />

                    <div v-for="(slot, index) in timeSlots" :key="index" class="rounded-lg border p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium">Time Slot {{ index + 1 }}</span>
                                <span v-if="slot.sponsor_ids.length > 0" class="text-xs italic text-muted-foreground">
                                    presented by {{ sponsors.filter((s) => slot.sponsor_ids.includes(s.id)).map((s) => s.name).join(', ') }}
                                </span>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="removeTimeSlot(index)"
                            >
                                <Trash2 class="size-4 text-destructive" />
                            </Button>
                        </div>

                        <input v-if="slot.id" type="hidden" :name="`time_slots[${index}][id]`" :value="slot.id" />
                        <input type="hidden" :name="`time_slots[${index}][sort_order]`" :value="slot.sort_order" />

                        <div class="grid gap-2">
                            <Label :for="`time_slots_${index}_name`">Name</Label>
                            <Input
                                :id="`time_slots_${index}_name`"
                                :name="`time_slots[${index}][name]`"
                                v-model="slot.name"
                                required
                                placeholder="Time slot name"
                            />
                            <InputError :message="errors[`time_slots.${index}.name`]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`time_slots_${index}_description`">Description</Label>
                            <Textarea
                                :id="`time_slots_${index}_description`"
                                :name="`time_slots[${index}][description]`"
                                v-model="slot.description"
                                rows="2"
                                placeholder="Short description…"
                            />
                            <InputError :message="errors[`time_slots.${index}.description`]" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label :for="`time_slots_${index}_starts_at`">Start Time</Label>
                                <Input
                                    :id="`time_slots_${index}_starts_at`"
                                    type="datetime-local"
                                    :name="`time_slots[${index}][starts_at]`"
                                    v-model="slot.starts_at"
                                    required
                                />
                                <InputError :message="errors[`time_slots.${index}.starts_at`]" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`time_slots_${index}_visibility`">Visibility</Label>
                                <Select
                                    :name="`time_slots[${index}][visibility]`"
                                    v-model="slot.visibility"
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="public">Public</SelectItem>
                                        <SelectItem value="internal">Internal</SelectItem>
                                        <SelectItem value="private">Private</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="errors[`time_slots.${index}.visibility`]" />
                            </div>
                        </div>

                        <!-- Time Slot Sponsors -->
                        <div v-if="sponsors.length > 0" class="space-y-2">
                            <Label>Sponsors</Label>
                            <div class="flex flex-wrap gap-x-4 gap-y-2">
                                <div v-for="sponsor in sponsors" :key="sponsor.id" class="flex items-center gap-2">
                                    <Checkbox
                                        :id="`time_slots_${index}_sponsor_${sponsor.id}`"
                                        :model-value="slot.sponsor_ids.includes(sponsor.id)"
                                        @update:model-value="(checked: boolean | 'indeterminate') => toggleSlotSponsor(index, sponsor.id, checked)"
                                    />
                                    <input
                                        v-if="slot.sponsor_ids.includes(sponsor.id)"
                                        type="hidden"
                                        :name="`time_slots[${index}][sponsor_ids][]`"
                                        :value="sponsor.id"
                                    />
                                    <Label :for="`time_slots_${index}_sponsor_${sponsor.id}`" class="cursor-pointer text-sm">
                                        {{ sponsor.name }}
                                        <span v-if="sponsor.sponsor_level" class="text-xs text-muted-foreground">({{ sponsor.sponsor_level.name }})</span>
                                    </Label>
                                </div>
                            </div>
                            <InputError :message="errors[`time_slots.${index}.sponsor_ids`]" />
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

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>

                    <p
                        v-if="recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >
                        Saved.
                    </p>
                </div>
            </Form>

            <!-- Delete section -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">Delete Program</h3>
                        <p class="text-sm text-muted-foreground">Permanently delete this program and all its time slots.</p>
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>
        </div>

        <!-- Primary program replacement confirmation dialog -->
        <Dialog v-model:open="showPrimaryConfirmDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Replace Primary Program?</DialogTitle>
                    <DialogDescription>
                        The event "{{ currentEvent?.name }}" already has "{{ currentEvent?.primary_program?.name }}" as its primary program.
                        This will replace it with "{{ program.name }}".
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="cancelReplacePrimary"
                    >
                        Cancel
                    </Button>
                    <Button @click="confirmReplacePrimary">
                        Replace Primary Program
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ program.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The program and all its time slots will be permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showDeleteDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="executeDelete"
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
