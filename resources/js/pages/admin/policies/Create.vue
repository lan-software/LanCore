<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('policies.admin.index.title'), href: '/admin/policies' },
    {
        title: t('policies.admin.create.title'),
        href: '/admin/policies/create',
    },
];
</script>

<template>
    <Head :title="$t('policies.admin.create.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    href="/admin/policies"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ $t('policies.admin.create.back') }}
                </Link>
            </div>

            <Heading
                :title="$t('policies.admin.create.title')"
                :description="$t('policies.admin.create.description')"
            />

            <Form
                action="/admin/policies"
                method="post"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">
                        {{ $t('policies.admin.create.name') }}
                    </Label>
                    <Input id="name" name="name" required />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="key">
                        {{ $t('policies.admin.create.key') }}
                    </Label>
                    <Input
                        id="key"
                        name="key"
                        required
                        :placeholder="
                            $t('policies.admin.create.key_placeholder')
                        "
                    />
                    <InputError :message="errors.key" />
                </div>

                <div class="grid gap-2">
                    <Label for="policy_type_id">
                        {{ $t('policies.admin.create.type') }}
                    </Label>
                    <Select name="policy_type_id">
                        <SelectTrigger>
                            <SelectValue
                                :placeholder="
                                    $t('policies.admin.create.type_placeholder')
                                "
                            />
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
                    <Label for="description">
                        {{ $t('policies.admin.create.policy_description') }}
                    </Label>
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
                        {{ $t('policies.admin.create.required_label') }}
                    </Label>
                </div>
                <InputError :message="errors.is_required_for_registration" />

                <div class="grid gap-2">
                    <Label for="sort_order">
                        {{ $t('policies.admin.create.sort_order') }}
                    </Label>
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
                        {{
                            processing
                                ? $t('policies.admin.create.submitting')
                                : $t('policies.admin.create.submit')
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
