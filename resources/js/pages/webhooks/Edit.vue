<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { update, destroy } from '@/actions/App/Domain/Webhook/Http/Controllers/WebhookController'
import { edit as webhookEdit } from '@/actions/App/Domain/Webhook/Http/Controllers/WebhookController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as webhooksRoute } from '@/routes/webhooks'
import type { BreadcrumbItem } from '@/types'
import type { Webhook } from '@/types/domain'

const props = defineProps<{
    webhook: Webhook
    events: { value: string; label: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: webhooksRoute().url },
    { title: 'Webhooks', href: webhooksRoute().url },
    { title: 'Edit', href: webhookEdit({ webhook: props.webhook.id }).url },
]

const form = useForm({
    name: props.webhook.name,
    url: props.webhook.url,
    event: props.webhook.event,
    secret: props.webhook.secret ?? '',
    description: props.webhook.description ?? '',
    is_active: props.webhook.is_active,
})

function submit() {
    form.patch(update({ webhook: props.webhook.id }).url, {
        preserveScroll: true,
    })
}

function deleteWebhook() {
    if (confirm('Are you sure you want to delete this webhook?')) {
        router.delete(destroy({ webhook: props.webhook.id }).url)
    }
}
</script>

<template>
    <Head title="Edit Webhook" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-3xl">
            <div class="flex items-center justify-between">
                <Link :href="webhooksRoute().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Webhooks
                </Link>
                <div class="flex items-center gap-2">
                    <Badge v-if="webhook.is_active" variant="default">Active</Badge>
                    <Badge v-else variant="outline">Inactive</Badge>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <Heading variant="small" title="Webhook Details" description="Update the webhook endpoint configuration" />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required placeholder="My Webhook" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="url">URL</Label>
                        <Input id="url" v-model="form.url" type="url" required placeholder="https://example.com/webhook" />
                        <InputError :message="form.errors.url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="event">Event</Label>
                        <Select v-model="form.event">
                            <SelectTrigger>
                                <SelectValue placeholder="Select an event" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="event in events" :key="event.value" :value="event.value">
                                    {{ event.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.event" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="secret">Secret <span class="text-muted-foreground font-normal">(optional)</span></Label>
                        <Input id="secret" v-model="form.secret" placeholder="Leave empty to keep existing secret or clear it" />
                        <InputError :message="form.errors.secret" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description <span class="text-muted-foreground font-normal">(optional)</span></Label>
                        <Textarea id="description" v-model="form.description" placeholder="Notes about this webhook" rows="3" />
                        <InputError :message="form.errors.description" />
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Status" description="Control whether this webhook is active" />

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <Label for="is_active">Active — send payloads when the event fires</Label>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                        </Transition>
                    </div>

                    <Button type="button" variant="destructive" @click="deleteWebhook">
                        Delete
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
