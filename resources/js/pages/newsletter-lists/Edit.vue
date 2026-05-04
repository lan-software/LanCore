<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import NewsletterListController from '@/actions/App/Domain/Newsletter/Http/Controllers/Admin/NewsletterListController';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { index as newsletterListsIndex } from '@/routes/newsletter-lists';
import type { BreadcrumbItem } from '@/types';

type NewsletterList = {
    id: number;
    listmonk_id: number;
    name: string;
    description: string | null;
    type: string;
    optin: string;
    tags: string[] | null;
    is_user_selectable: boolean;
    is_default_public: boolean;
    last_synced_at: string | null;
};

const props = defineProps<{
    list: NewsletterList;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: newsletterListsIndex().url },
    { title: 'Newsletter Lists', href: newsletterListsIndex().url },
    {
        title: props.list.name,
        href: NewsletterListController.edit(props.list.id).url,
    },
];

const form = useForm({
    name: props.list.name,
    description: props.list.description ?? '',
    type: props.list.type as 'private' | 'public',
    optin: props.list.optin as 'single' | 'double',
    is_user_selectable: props.list.is_user_selectable,
    is_default_public: props.list.is_default_public,
});

function submit() {
    form.patch(NewsletterListController.update(props.list.id).url);
}
</script>

<template>
    <Head :title="`Edit ${list.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div>
                <Link
                    :href="newsletterListsIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to lists
                </Link>
            </div>

            <form @submit.prevent="submit" class="grid max-w-2xl gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Input id="description" v-model="form.description" />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="type">Visibility in Listmonk</Label>
                    <Select v-model="form.type">
                        <SelectTrigger id="type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="private">Private</SelectItem>
                            <SelectItem value="public">Public</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.type" />
                </div>

                <div class="grid gap-2">
                    <Label for="optin">Opt-in mode</Label>
                    <Select v-model="form.optin">
                        <SelectTrigger id="optin">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="single">Single</SelectItem>
                            <SelectItem value="double">
                                Double (confirmation email)
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.optin" />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_user_selectable"
                        v-model="form.is_user_selectable"
                    />
                    <Label for="is_user_selectable">
                        User selectable on the E-Mail Settings page
                    </Label>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_default_public"
                        v-model="form.is_default_public"
                    />
                    <Label for="is_default_public">
                        Use as the default public list on the countdown signup
                        form
                    </Label>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save changes' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
