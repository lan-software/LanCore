<script setup lang="ts">
import EventController from '@/actions/App/Domain/Event/Http/Controllers/EventController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as eventsRoute } from '@/routes/events'
import type { BreadcrumbItem } from '@/types'
import { Form, Head, Link } from '@inertiajs/vue3'
import { ImagePlus } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps<{
    venues: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: eventsRoute().url },
    { title: 'Events', href: eventsRoute().url },
    { title: 'Create', href: EventController.create().url },
]

const bannerPreview = ref<string | null>(null)

function onBannerSelected(event: globalThis.Event) {
    const file = (event.target as HTMLInputElement).files?.[0]
    if (file) {
        bannerPreview.value = URL.createObjectURL(file)
    } else {
        bannerPreview.value = null
    }
}
</script>

<template>
    <Head title="Create Event" />

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

                <!-- Venue & Media -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Venue & Media"
                        description="Optionally assign a venue and banner image"
                    />

                    <div class="grid gap-2">
                        <Label for="venue_id">Venue</Label>
                        <Select name="venue_id">
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
                                Choose Image
                            </label>
                            <input
                                id="banner_image"
                                type="file"
                                name="banner_image"
                                accept="image/jpeg,image/png,image/gif,image/webp"
                                class="sr-only"
                                @change="onBannerSelected"
                            />
                        </div>
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

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Creating…' : 'Create Event' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
