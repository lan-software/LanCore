<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
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

defineProps<{
    policyTypes: { id: number; label: string; key: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Policies', href: '/admin/policies' },
    { title: 'New policy', href: '/admin/policies/create' },
];
</script>

<template>
    <Head title="New policy" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    href="/admin/policies"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Policies
                </Link>
            </div>

            <Heading
                title="New policy"
                description="Create a new policy. You can publish its first version on the next page."
            />

            <Form
                action="/admin/policies"
                method="post"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" required />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="key">Key (slug)</Label>
                    <Input
                        id="key"
                        name="key"
                        required
                        placeholder="e.g. tos, privacy, code-of-conduct"
                    />
                    <InputError :message="errors.key" />
                </div>

                <div class="grid gap-2">
                    <Label for="policy_type_id">Type</Label>
                    <Select name="policy_type_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select a policy type" />
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
                    <Textarea id="description" name="description" rows="3" />
                    <InputError :message="errors.description" />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_required_for_registration"
                        name="is_required_for_registration"
                        :value="1"
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
                        value="0"
                    />
                    <InputError :message="errors.sort_order" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create policy' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
