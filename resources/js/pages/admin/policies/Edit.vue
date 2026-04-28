<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
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
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    policy: {
        id: number;
        key: string;
        name: string;
        description: string | null;
        is_required_for_registration: boolean;
        sort_order: number;
        policy_type_id: number;
    };
    policyTypes: { id: number; label: string; key: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Policies', href: '/admin/policies' },
    { title: props.policy.name, href: `/admin/policies/${props.policy.id}` },
    { title: 'Edit', href: `/admin/policies/${props.policy.id}/edit` },
];

function archive(): void {
    if (
        confirm(
            'Archive this policy? It will no longer apply to new acceptances or registration. This action does not cascade-delete versions.',
        )
    ) {
        router.post(`/admin/policies/${props.policy.id}/archive`);
    }
}
</script>

<template>
    <Head :title="`Edit ${policy.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="`/admin/policies/${policy.id}`"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to policy
                </Link>
            </div>

            <Heading
                :title="`Edit ${policy.name}`"
                description="Edit metadata. Content is managed by publishing new versions."
            />

            <Form
                :action="`/admin/policies/${policy.id}`"
                method="put"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="policy.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="key">Key (slug)</Label>
                    <Input
                        id="key"
                        name="key"
                        :default-value="policy.key"
                        required
                    />
                    <InputError :message="errors.key" />
                </div>

                <div class="grid gap-2">
                    <Label for="policy_type_id">Type</Label>
                    <Select
                        name="policy_type_id"
                        :default-value="String(policy.policy_type_id)"
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="type in policyTypes"
                                :key="type.id"
                                :value="String(type.id)"
                            >
                                {{ type.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.policy_type_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        name="description"
                        rows="3"
                        :default-value="policy.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_required_for_registration"
                        name="is_required_for_registration"
                        :value="1"
                        :default-value="policy.is_required_for_registration"
                    />
                    <Label
                        for="is_required_for_registration"
                        class="cursor-pointer"
                    >
                        Required at registration
                    </Label>
                </div>
                <InputError :message="errors.is_required_for_registration" />

                <div class="grid gap-2">
                    <Label for="sort_order">Sort order</Label>
                    <Input
                        id="sort_order"
                        name="sort_order"
                        type="number"
                        min="0"
                        :default-value="policy.sort_order"
                    />
                    <InputError :message="errors.sort_order" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save' }}
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="archive"
                    >
                        Archive
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
