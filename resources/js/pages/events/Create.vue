<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ImagePlus, X } from 'lucide-vue-next';
import { ref } from 'vue';
import EventController from '@/actions/App/Domain/Event/Http/Controllers/EventController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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

defineProps<{
    venues: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: eventsRoute().url },
    { title: 'Events', href: eventsRoute().url },
    { title: 'Create', href: EventController.create().url },
];

const bannerPreviews = ref<{ id: number; preview: string }[]>([]);
let nextBannerId = 0;

function addBannerSlot() {
    bannerPreviews.value.push({ id: nextBannerId++, preview: '' });
}

function onBannerSelected(index: number, event: globalThis.Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        bannerPreviews.value[index].preview = URL.createObjectURL(file);
    }
}

function removeBannerSlot(index: number) {
    const preview = bannerPreviews.value[index].preview;

    if (preview) {
        URL.revokeObjectURL(preview);
    }

    bannerPreviews.value.splice(index, 1);
}
</script>

<template>
    <Head title="Create Event" />

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

            <Form
                v-bind="EventController.store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Event Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Event Information"
                        description="Provide the basic details for this event"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
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
                        description="Set the start and end date and time"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">Start Date & Time</Label>
                            <Input
                                id="start_date"
                                type="datetime-local"
                                name="start_date"
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
                        description="Optionally assign a venue and set the seating capacity"
                    />

                    <div class="grid gap-2">
                        <Label for="venue_id">Venue</Label>
                        <Select name="venue_id">
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
                        description="Optionally add one or more banner images. Multiple images will cycle automatically."
                    />

                    <div class="grid gap-3">
                        <Label>Banner Images</Label>

                        <!-- Existing image slots -->
                        <div
                            v-for="(slot, index) in bannerPreviews"
                            :key="slot.id"
                            class="flex items-start gap-3"
                        >
                            <div class="flex-1">
                                <label
                                    :for="`banner_image_${slot.id}`"
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
                                    :id="`banner_image_${slot.id}`"
                                    type="file"
                                    name="banner_images[]"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="sr-only"
                                    @change="onBannerSelected(index, $event)"
                                />
                                <img
                                    v-if="slot.preview"
                                    :src="slot.preview"
                                    alt="Banner preview"
                                    class="mt-2 max-h-36 rounded-md border object-cover"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="mt-1 shrink-0"
                                @click="removeBannerSlot(index)"
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
                        <InputError
                            :message="
                                (errors as Record<string, string>)[
                                    'banner_images.0'
                                ]
                            "
                        />
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create Event' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
