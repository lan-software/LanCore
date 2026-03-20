<script setup lang="ts">
import { store } from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorController'
import { create as sponsorCreate } from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as sponsorsRoute } from '@/routes/sponsors'
import type { BreadcrumbItem } from '@/types'
import type { SponsorLevel } from '@/types/domain'
import { Form, Head, Link } from '@inertiajs/vue3'

defineProps<{
    sponsorLevels: SponsorLevel[]
    events: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: sponsorsRoute().url },
    { title: 'Sponsors', href: sponsorsRoute().url },
    { title: 'Create', href: sponsorCreate().url },
]
</script>

<template>
    <Head title="Create Sponsor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <!-- Back link -->
            <div>
                <Link
                    :href="sponsorsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Sponsors
                </Link>
            </div>

            <Form
                v-bind="store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
                enctype="multipart/form-data"
            >
                <!-- Sponsor Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Sponsor Information"
                        description="Provide the basic details for this sponsor"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="Sponsor name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="Short description of the sponsor…"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="link">Website Link</Label>
                        <Input
                            id="link"
                            name="link"
                            type="url"
                            placeholder="https://sponsor-website.com"
                        />
                        <InputError :message="errors.link" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="logo">Logo</Label>
                        <Input
                            id="logo"
                            name="logo"
                            type="file"
                            accept="image/*"
                        />
                        <InputError :message="errors.logo" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="sponsor_level_id">Sponsor Level</Label>
                        <Select name="sponsor_level_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a level (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="level in sponsorLevels"
                                    :key="level.id"
                                    :value="String(level.id)"
                                >
                                    <span class="flex items-center gap-2">
                                        <span class="size-3 rounded-full" :style="{ backgroundColor: level.color }" />
                                        {{ level.name }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.sponsor_level_id" />
                    </div>
                </div>

                <!-- Events -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Events"
                        description="Assign this sponsor to events"
                    />

                    <div v-if="events.length > 0" class="space-y-2">
                        <div v-for="event in events" :key="event.id" class="flex items-center gap-2">
                            <Checkbox
                                :id="`event-${event.id}`"
                                name="event_ids[]"
                                :value="event.id"
                            />
                            <Label :for="`event-${event.id}`" class="cursor-pointer">{{ event.name }}</Label>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No events available.</p>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Creating…' : 'Create Sponsor' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
