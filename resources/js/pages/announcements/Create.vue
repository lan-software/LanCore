<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { store } from '@/actions/App/Domain/Announcement/Http/Controllers/AnnouncementController'
import { create as announcementCreate } from '@/actions/App/Domain/Announcement/Http/Controllers/AnnouncementController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as announcementsRoute } from '@/routes/announcements'
import type { BreadcrumbItem } from '@/types'

const props = defineProps<{
    events: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: announcementsRoute().url },
    { title: 'Announcements', href: announcementsRoute().url },
    { title: 'Create', href: announcementCreate().url },
]

const form = useForm({
    title: '',
    description: '',
    priority: 'normal',
    event_id: '',
    publish_now: false,
})

function submit() {
    form.transform((data) => ({
        ...data,
        publish_now: data.publish_now ? true : false,
    })).post(store().url)
}
</script>

<template>
    <Head title="Create Announcement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-3xl">
            <div>
                <Link :href="announcementsRoute().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Announcements
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <Heading variant="small" title="Announcement Information" description="Create a new announcement for event attendees" />

                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input id="title" v-model="form.title" required placeholder="Announcement title" />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea id="description" v-model="form.description" placeholder="Provide details about the announcement" rows="4" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="event_id">Event</Label>
                        <Select v-model="form.event_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select an event" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="event in events" :key="event.id" :value="String(event.id)">
                                    {{ event.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.event_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="priority">Priority</Label>
                        <Select v-model="form.priority">
                            <SelectTrigger>
                                <SelectValue placeholder="Select priority" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="silent">Silent — no notifications sent</SelectItem>
                                <SelectItem value="normal">Normal — respects user preferences</SelectItem>
                                <SelectItem value="emergency">Emergency — always notifies all users</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.priority" />
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Publishing" description="Control when this announcement is visible" />

                    <div class="flex items-center gap-2">
                        <Checkbox id="publish_now" v-model:checked="form.publish_now" />
                        <Label for="publish_now">Publish immediately</Label>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Announcement' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
