<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { store } from '@/actions/App/Domain/Achievements/Http/Controllers/AchievementController'
import { create as achievementCreate } from '@/actions/App/Domain/Achievements/Http/Controllers/AchievementController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as achievementsRoute } from '@/routes/achievements'
import type { BreadcrumbItem } from '@/types'
import type { GrantableEvent } from '@/types/domain'

const props = defineProps<{
    grantableEvents: GrantableEvent[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: achievementsRoute().url },
    { title: 'Achievements', href: achievementsRoute().url },
    { title: 'Create', href: achievementCreate().url },
]

const form = useForm({
    name: '',
    description: '',
    notification_text: '',
    color: '#3b82f6',
    icon: 'trophy',
    is_active: true,
    event_classes: [] as string[],
})

function toggleEvent(eventClass: string) {
    const index = form.event_classes.indexOf(eventClass)
    if (index === -1) {
        form.event_classes.push(eventClass)
    } else {
        form.event_classes.splice(index, 1)
    }
}

function submit() {
    form.post(store().url)
}
</script>

<template>
    <Head title="Create Achievement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-3xl">
            <div>
                <Link :href="achievementsRoute().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Achievements
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <Heading variant="small" title="Achievement Information" description="Configure the achievement badge details" />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required placeholder="Achievement name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea id="description" v-model="form.description" placeholder="Describe what this achievement represents" rows="3" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="notification_text">Notification Text</Label>
                        <Textarea id="notification_text" v-model="form.notification_text" placeholder="Text shown when a user earns this achievement" rows="2" />
                        <p class="text-xs text-muted-foreground">Max 500 characters. Sent to the user when they earn the badge.</p>
                        <InputError :message="form.errors.notification_text" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="color">Color</Label>
                            <div class="flex items-center gap-2">
                                <input
                                    id="color"
                                    v-model="form.color"
                                    type="color"
                                    class="size-10 cursor-pointer rounded border border-input"
                                />
                                <Input v-model="form.color" class="flex-1 font-mono" placeholder="#3b82f6" />
                            </div>
                            <InputError :message="form.errors.color" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="icon">Icon</Label>
                            <Input id="icon" v-model="form.icon" placeholder="e.g. trophy, star, medal" />
                            <InputError :message="form.errors.icon" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <Label for="is_active" class="text-sm font-normal">Active</Label>
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Trigger Events" description="Select which events will grant this achievement to users" />

                    <div class="space-y-3">
                        <div v-for="event in grantableEvents" :key="event.value" class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                :id="`event-${event.value}`"
                                :checked="form.event_classes.includes(event.value)"
                                class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                                @change="toggleEvent(event.value)"
                            />
                            <Label :for="`event-${event.value}`" class="text-sm font-normal">
                                {{ event.label }}
                            </Label>
                        </div>
                    </div>
                    <InputError :message="form.errors.event_classes" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Achievement' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
