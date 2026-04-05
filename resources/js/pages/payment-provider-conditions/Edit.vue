<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import PaymentProviderConditionController from '@/actions/App/Domain/Shop/Http/Controllers/PaymentProviderConditionController';
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
import { index as conditionsIndex } from '@/routes/payment-provider-conditions';
import type { BreadcrumbItem } from '@/types';

type Condition = {
    id: number;
    payment_method: string;
    name: string;
    description: string | null;
    content: string | null;
    acknowledgement_label: string;
    is_required: boolean;
    is_active: boolean;
    requires_scroll: boolean;
    sort_order: number;
};

const props = defineProps<{
    condition: Condition;
    paymentMethods: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: conditionsIndex().url },
    { title: 'Payment Provider Conditions', href: conditionsIndex().url },
    {
        title: 'Edit',
        href: PaymentProviderConditionController.edit(props.condition.id).url,
    },
];

function deleteCondition() {
    if (confirm('Are you sure you want to delete this condition?')) {
        router.delete(
            PaymentProviderConditionController.destroy(props.condition.id).url,
        );
    }
}
</script>

<template>
    <Head title="Edit Payment Provider Condition" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <div class="flex items-center justify-between">
                <Link
                    :href="conditionsIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Conditions
                </Link>
                <Button variant="destructive" size="sm" @click="deleteCondition"
                    >Delete</Button
                >
            </div>

            <Form
                v-bind="
                    PaymentProviderConditionController.update.form(condition.id)
                "
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Condition Details"
                        description="Edit the payment provider condition"
                    />

                    <div class="grid gap-2">
                        <Label for="payment_method">Payment Method</Label>
                        <Select
                            name="payment_method"
                            :default-value="condition.payment_method"
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Select payment method"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="method in paymentMethods"
                                    :key="method.value"
                                    :value="method.value"
                                >
                                    {{ method.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.payment_method" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            :default-value="condition.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            name="description"
                            :default-value="condition.description ?? ''"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="content">Content (Rich Text / HTML)</Label>
                        <textarea
                            id="content"
                            name="content"
                            rows="6"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            :value="condition.content ?? ''"
                        />
                        <InputError :message="errors.content" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="acknowledgement_label"
                            >Acknowledgement Label</Label
                        >
                        <Input
                            id="acknowledgement_label"
                            name="acknowledgement_label"
                            required
                            :default-value="condition.acknowledgement_label"
                        />
                        <InputError :message="errors.acknowledgement_label" />
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Settings"
                        description="Configure behaviour"
                    />

                    <div class="grid gap-2">
                        <Label for="sort_order">Sort Order</Label>
                        <Input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            :default-value="condition.sort_order"
                        />
                        <InputError :message="errors.sort_order" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_required"
                            name="is_required"
                            :default-value="condition.is_required"
                        />
                        <Label for="is_required" class="cursor-pointer"
                            >Required</Label
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            :default-value="condition.is_active"
                        />
                        <Label for="is_active" class="cursor-pointer"
                            >Active</Label
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="requires_scroll"
                            name="requires_scroll"
                            :default-value="condition.requires_scroll"
                        />
                        <Label for="requires_scroll" class="cursor-pointer"
                            >Require scroll to bottom before accepting</Label
                        >
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
