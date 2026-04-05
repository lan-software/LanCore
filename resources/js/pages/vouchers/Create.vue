<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import VoucherController from '@/actions/App/Domain/Shop/Http/Controllers/VoucherController';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { index as vouchersIndex } from '@/routes/vouchers';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    events: { id: number; name: string }[];
    selectedEventId?: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: vouchersIndex().url },
    { title: 'Vouchers', href: vouchersIndex().url },
    { title: 'Create', href: VoucherController.create().url },
];
</script>

<template>
    <Head title="Create Voucher" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="vouchersIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Vouchers
                </Link>
            </div>

            <Form
                v-bind="VoucherController.store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Voucher Information"
                        description="Basic details for this voucher"
                    />

                    <div class="grid gap-2">
                        <Label for="code">Code</Label>
                        <Input
                            id="code"
                            name="code"
                            required
                            placeholder="SUMMER2026"
                            class="font-mono"
                        />
                        <InputError :message="errors.code" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="type">Discount Type</Label>
                        <Select name="type">
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Select discount type"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="fixed_amount"
                                    >Fixed Amount</SelectItem
                                >
                                <SelectItem value="percentage"
                                    >Percentage</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.type" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="discount_amount"
                                >Discount Amount (cents)</Label
                            >
                            <Input
                                id="discount_amount"
                                name="discount_amount"
                                type="number"
                                min="0"
                                placeholder="e.g. 500 for 5.00 €"
                            />
                            <InputError :message="errors.discount_amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="discount_percent"
                                >Discount Percent</Label
                            >
                            <Input
                                id="discount_percent"
                                name="discount_percent"
                                type="number"
                                min="0"
                                max="100"
                                placeholder="e.g. 10"
                            />
                            <InputError :message="errors.discount_percent" />
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Usage Limits"
                        description="Set usage and validity constraints"
                    />

                    <div class="grid gap-2">
                        <Label for="max_uses">Max Uses</Label>
                        <Input
                            id="max_uses"
                            name="max_uses"
                            type="number"
                            min="1"
                            placeholder="Leave empty for unlimited"
                        />
                        <InputError :message="errors.max_uses" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="valid_from">Valid From</Label>
                            <Input
                                id="valid_from"
                                name="valid_from"
                                type="datetime-local"
                            />
                            <InputError :message="errors.valid_from" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="valid_until">Valid Until</Label>
                            <Input
                                id="valid_until"
                                name="valid_until"
                                type="datetime-local"
                            />
                            <InputError :message="errors.valid_until" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            :default-value="true"
                        />
                        <Label for="is_active" class="cursor-pointer"
                            >Active</Label
                        >
                    </div>
                    <InputError :message="errors.is_active" />
                </div>

                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Association"
                        description="Optionally limit to a specific event"
                    />

                    <div class="grid gap-2">
                        <Label for="event_id">Event</Label>
                        <Select
                            name="event_id"
                            :default-value="
                                selectedEventId
                                    ? String(selectedEventId)
                                    : undefined
                            "
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="All events (optional)"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="event in events"
                                    :key="event.id"
                                    :value="String(event.id)"
                                >
                                    {{ event.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.event_id" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create Voucher' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
