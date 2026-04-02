<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ImagePlus, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';
import EventController from '@/actions/App/Domain/Event/Http/Controllers/EventController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { index as eventsRoute } from '@/routes/events';
import type { BreadcrumbItem } from '@/types';
import type { Event } from '@/types/domain';

const props = defineProps<{
    event: Event;
    venues: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: eventsRoute().url },
    { title: 'Events', href: eventsRoute().url },
    { title: props.event.name, href: EventController.edit(props.event.id).url },
];

function formatDateTimeLocal(dateString: string): string {
    const date = new Date(dateString);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

const showDeleteDialog = ref(false);
const publishErrors = ref<Record<string, string>>({});

// Existing images still retained (not yet marked for removal).
const retainedImages = ref<{ path: string; url: string }[]>(
    (props.event.banner_images ?? []).map((path, i) => ({
        path,
        url: props.event.banner_image_urls[i] ?? '',
    })),
);
const imagesToRemove = ref<string[]>([]);

function markImageForRemoval(index: number) {
    const img = retainedImages.value[index];
    imagesToRemove.value.push(img.path);
    retainedImages.value.splice(index, 1);
}

// Newly picked images (not yet uploaded).
const newBannerSlots = ref<{ id: number; preview: string }[]>([]);
let nextSlotId = 0;

function addBannerSlot() {
    newBannerSlots.value.push({ id: nextSlotId++, preview: '' });
}

function onNewBannerSelected(index: number, event: globalThis.Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        newBannerSlots.value[index].preview = URL.createObjectURL(file);
    }
}

function removeNewBannerSlot(index: number) {
    const preview = newBannerSlots.value[index].preview;

    if (preview) {
        URL.revokeObjectURL(preview);
    }

    newBannerSlots.value.splice(index, 1);
}

function executeDelete() {
    router.delete(EventController.destroy(props.event.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}

function publishEvent() {
    publishErrors.value = {};
    router.patch(
        EventController.publish(props.event.id).url,
        {},
        {
            preserveScroll: true,
            onError: (errors) => {
                publishErrors.value = errors;
            },
        },
    );
}

function unpublishEvent() {
    router.patch(
        EventController.unpublish(props.event.id).url,
        {},
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head :title="`Edit ${event.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="eventsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Events
                </Link>
            </div>

            <!-- Status & Publishing -->
            <div class="flex items-center gap-4">
                <Badge
                    :variant="
                        event.status === 'published' ? 'default' : 'secondary'
                    "
                >
                    {{ event.status === 'published' ? 'Published' : 'Draft' }}
                </Badge>
                <Button
                    v-if="event.status === 'draft'"
                    size="sm"
                    @click="publishEvent"
                >
                    Publish
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    @click="unpublishEvent"
                >
                    Unpublish
                </Button>
            </div>
            <div v-if="Object.keys(publishErrors).length > 0" class="space-y-1">
                <p
                    v-for="(message, field) in publishErrors"
                    :key="field"
                    class="text-sm text-destructive"
                >
                    {{ message }}
                </p>
            </div>

            <Form
                v-bind="EventController.update.form(event.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Event Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Event Information"
                        description="Update the basic details for this event"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="event.name"
                            required
                            placeholder="Event name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            :default-value="event.description ?? ''"
                            rows="4"
                            placeholder="Describe the event…"
                        />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <!-- Schedule -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Schedule"
                        description="Update the start and end date and time"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">Start Date & Time</Label>
                            <Input
                                id="start_date"
                                type="datetime-local"
                                name="start_date"
                                :default-value="
                                    formatDateTimeLocal(event.start_date)
                                "
                                required
                            />
                            <InputError :message="errors.start_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_date">End Date & Time</Label>
                            <Input
                                id="end_date"
                                type="datetime-local"
                                name="end_date"
                                :default-value="
                                    formatDateTimeLocal(event.end_date)
                                "
                                required
                            />
                            <InputError :message="errors.end_date" />
                        </div>
                    </div>
                </div>

                <!-- Venue & Capacity -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Venue & Capacity"
                        description="Update the venue and seating capacity"
                    />

                    <div class="grid gap-2">
                        <Label for="venue_id">Venue</Label>
                        <Select
                            name="venue_id"
                            :default-value="
                                event.venue_id
                                    ? String(event.venue_id)
                                    : undefined
                            "
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Select a venue (optional)"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="venue in venues"
                                    :key="venue.id"
                                    :value="String(venue.id)"
                                >
                                    {{ venue.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.venue_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="seat_capacity">Seating Capacity</Label>
                        <Input
                            id="seat_capacity"
                            type="number"
                            name="seat_capacity"
                            :default-value="
                                event.seat_capacity
                                    ? String(event.seat_capacity)
                                    : ''
                            "
                            min="0"
                            placeholder="Leave empty for unlimited"
                        />
                        <InputError :message="errors.seat_capacity" />
                    </div>
                </div>

                <!-- Media -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Media"
                        description="Add or remove banner images. Multiple images will cycle automatically."
                    />

                    <div class="grid gap-3">
                        <Label>Banner Images</Label>

                        <!-- Retained existing images -->
                        <div
                            v-for="(img, index) in retainedImages"
                            :key="img.path"
                            class="flex items-start gap-3"
                        >
                            <img
                                :src="img.url"
                                alt="Banner image"
                                class="h-20 w-36 shrink-0 rounded-md border object-cover"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="mt-1"
                                @click="markImageForRemoval(index)"
                            >
                                <X class="size-4" />
                                Remove
                            </Button>
                        </div>

                        <!-- Hidden inputs for images to remove -->
                        <template v-for="path in imagesToRemove" :key="path">
                            <input
                                type="hidden"
                                name="banner_images_to_remove[]"
                                :value="path"
                            />
                        </template>

                        <!-- New image upload slots -->
                        <div
                            v-for="(slot, index) in newBannerSlots"
                            :key="slot.id"
                            class="flex items-start gap-3"
                        >
                            <div class="flex-1">
                                <label
                                    :for="`new_banner_${slot.id}`"
                                    class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground ring-offset-background hover:bg-accent hover:text-accent-foreground"
                                >
                                    <ImagePlus class="size-4" />
                                    {{
                                        slot.preview
                                            ? 'Replace'
                                            : 'Choose Image'
                                    }}
                                </label>
                                <input
                                    :id="`new_banner_${slot.id}`"
                                    type="file"
                                    name="banner_images[]"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="sr-only"
                                    @change="onNewBannerSelected(index, $event)"
                                />
                                <img
                                    v-if="slot.preview"
                                    :src="slot.preview"
                                    alt="New banner preview"
                                    class="mt-2 max-h-36 rounded-md border object-cover"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="mt-1 shrink-0"
                                @click="removeNewBannerSlot(index)"
                            >
                                <X class="size-4" />
                                Remove
                            </Button>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="w-fit"
                            @click="addBannerSlot"
                        >
                            <ImagePlus class="size-4" />
                            Add Image
                        </Button>

                        <p class="text-xs text-muted-foreground">
                            Accepted formats: JPEG, PNG, GIF, WebP. Max 5 MB
                            each.
                        </p>
                        <InputError
                            :message="
                                (errors as Record<string, string>)[
                                    'banner_images'
                                ]
                            "
                        />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
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
                        <h3 class="text-sm font-medium text-destructive">
                            Delete Event
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Permanently delete this event.
                        </p>
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

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ event.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The event will be
                        permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="executeDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
