<script setup lang="ts">
import EventController from '@/actions/App/Domain/Event/Http/Controllers/EventController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as eventsRoute } from '@/routes/events'
import type { BreadcrumbItem } from '@/types'
import type { Event } from '@/types/domain'
import { Form, Head, Link, router } from '@inertiajs/vue3'
import { ImagePlus, Trash2, X } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps<{
    event: Event
    venues: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: eventsRoute().url },
    { title: 'Events', href: eventsRoute().url },
    { title: props.event.name, href: EventController.edit(props.event.id).url },
]

function formatDateTimeLocal(dateString: string): string {
    const date = new Date(dateString)
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const showDeleteDialog = ref(false)
const publishErrors = ref<Record<string, string>>({})
const bannerPreview = ref<string | null>(props.event.banner_image_url)
const removeBanner = ref(false)

function onBannerSelected(event: globalThis.Event) {
    const file = (event.target as HTMLInputElement).files?.[0]
    if (file) {
        bannerPreview.value = URL.createObjectURL(file)
        removeBanner.value = false
    }
}

function clearBanner() {
    bannerPreview.value = null
    removeBanner.value = true
    const fileInput = document.getElementById('banner_image') as HTMLInputElement
    if (fileInput) {
        fileInput.value = ''
    }
}

function executeDelete() {
    router.delete(EventController.destroy(props.event.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false
        },
    })
}

function publishEvent() {
    publishErrors.value = {}
    router.patch(EventController.publish(props.event.id).url, {}, {
        preserveScroll: true,
        onError: (errors) => {
            publishErrors.value = errors
        },
    })
}

function unpublishEvent() {
    router.patch(EventController.unpublish(props.event.id).url, {}, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="`Edit ${event.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
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
                <Badge :variant="event.status === 'published' ? 'default' : 'secondary'">
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
                <p v-for="(message, field) in publishErrors" :key="field" class="text-sm text-destructive">
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
                                :default-value="formatDateTimeLocal(event.start_date)"
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
                                :default-value="formatDateTimeLocal(event.end_date)"
                                required
                            />
                            <InputError :message="errors.end_date" />
                        </div>
                    </div>
                </div>

                <!-- Venue & Media -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Venue & Media"
                        description="Optionally assign a venue and banner image"
                    />

                    <div class="grid gap-2">
                        <Label for="venue_id">Venue</Label>
                        <Select
                            name="venue_id"
                            :default-value="event.venue_id ? String(event.venue_id) : undefined"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select a venue (optional)" />
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
                        <Label for="banner_image">Banner Image</Label>
                        <div class="flex items-center gap-4">
                            <label
                                for="banner_image"
                                class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground ring-offset-background hover:bg-accent hover:text-accent-foreground"
                            >
                                <ImagePlus class="size-4" />
                                {{ bannerPreview ? 'Replace Image' : 'Choose Image' }}
                            </label>
                            <input
                                id="banner_image"
                                type="file"
                                name="banner_image"
                                accept="image/jpeg,image/png,image/gif,image/webp"
                                class="sr-only"
                                @change="onBannerSelected"
                            />
                            <Button
                                v-if="bannerPreview"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="clearBanner"
                            >
                                <X class="size-4" />
                                Remove
                            </Button>
                        </div>
                        <input
                            v-if="removeBanner"
                            type="hidden"
                            name="remove_banner_image"
                            value="1"
                        />
                        <img
                            v-if="bannerPreview"
                            :src="bannerPreview"
                            alt="Banner preview"
                            class="mt-2 max-h-48 rounded-md border object-cover"
                        />
                        <p class="text-xs text-muted-foreground">Accepted formats: JPEG, PNG, GIF, WebP. Max 5 MB.</p>
                        <InputError :message="errors.banner_image" />
                    </div>
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
                        <h3 class="text-sm font-medium text-destructive">Delete Event</h3>
                        <p class="text-sm text-muted-foreground">Permanently delete this event.</p>
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
                        This action cannot be undone. The event will be permanently removed.
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
