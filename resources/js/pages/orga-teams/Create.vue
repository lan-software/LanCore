<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
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
import { index as orgaTeamsRoute, store } from '@/routes/orga-teams';
import type { BreadcrumbItem } from '@/types';

type UserOption = { id: number; username: string | null; name: string };

defineProps<{
    users: UserOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Orga-Teams', href: orgaTeamsRoute().url },
    { title: 'New', href: '#' },
];

const form = useForm({
    name: '',
    slug: '',
    description: '',
    organizer_user_id: '' as number | '',
});

function submit() {
    form.transform((data) => ({
        ...data,
        organizer_user_id: data.organizer_user_id || null,
    })).post(store().url);
}

function slugify(value: string): string {
    return value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}
</script>

<template>
    <Head title="Create Orga-Team" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-4">
            <Heading
                title="New Orga-Team"
                description="Create a reusable team you can later assign to events"
            />

            <form @submit.prevent="submit" class="mt-6 space-y-5">
                <div>
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        @input="form.slug = form.slug || slugify(form.name)"
                        required
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-destructive"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <Label for="slug">Slug</Label>
                    <Input id="slug" v-model="form.slug" required />
                    <p
                        v-if="form.errors.slug"
                        class="mt-1 text-sm text-destructive"
                    >
                        {{ form.errors.slug }}
                    </p>
                </div>

                <div>
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                    />
                </div>

                <div>
                    <Label for="organizer">Organizer (Veranstalter)</Label>
                    <Select
                        :model-value="
                            form.organizer_user_id === ''
                                ? ''
                                : String(form.organizer_user_id)
                        "
                        @update:model-value="
                            (v) => (form.organizer_user_id = v ? Number(v) : '')
                        "
                    >
                        <SelectTrigger id="organizer">
                            <SelectValue placeholder="Pick the organizer" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="u in users"
                                :key="u.id"
                                :value="String(u.id)"
                            >
                                {{ u.name
                                }}<span v-if="u.username">
                                    (@{{ u.username }})</span
                                >
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.organizer_user_id"
                        class="mt-1 text-sm text-destructive"
                    >
                        {{ form.errors.organizer_user_id }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Button :disabled="form.processing" type="submit">
                        Create
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
