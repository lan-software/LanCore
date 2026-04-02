<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Domain/Webhook/Http/Controllers/WebhookController';
import { create as webhookCreate } from '@/actions/App/Domain/Webhook/Http/Controllers/WebhookController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { index as webhooksRoute } from '@/routes/webhooks';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    events: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: webhooksRoute().url },
    { title: 'Webhooks', href: webhooksRoute().url },
    { title: 'Create', href: webhookCreate().url },
];

const form = useForm({
    name: '',
    url: '',
    event: '',
    secret: '',
    description: '',
    is_active: true,
});

function submit() {
    form.post(store().url);
}
</script>

<template>
    <Head title="Create Webhook" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="webhooksRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Webhooks
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Webhook Details"
                        description="Configure an outgoing webhook endpoint"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="My Webhook"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="url">URL</Label>
                        <Input
                            id="url"
                            v-model="form.url"
                            type="url"
                            required
                            placeholder="https://example.com/webhook"
                        />
                        <InputError :message="form.errors.url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="event">Event</Label>
                        <Select v-model="form.event">
                            <SelectTrigger>
                                <SelectValue placeholder="Select an event" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="event in events"
                                    :key="event.value"
                                    :value="event.value"
                                >
                                    {{ event.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.event" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="secret"
                            >Secret
                            <span class="font-normal text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <Input
                            id="secret"
                            v-model="form.secret"
                            placeholder="Used to sign webhook payloads (HMAC-SHA256)"
                        />
                        <InputError :message="form.errors.secret" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description"
                            >Description
                            <span class="font-normal text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Notes about this webhook"
                            rows="3"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Status"
                        description="Control whether this webhook is active"
                    />

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            v-model:checked="form.is_active"
                        />
                        <Label for="is_active"
                            >Active — send payloads when the event fires</Label
                        >
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Webhook' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
