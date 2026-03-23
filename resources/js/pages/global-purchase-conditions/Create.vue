<script setup lang="ts">
import GlobalPurchaseConditionController from '@/actions/App/Domain/Shop/Http/Controllers/GlobalPurchaseConditionController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as conditionsIndex } from '@/routes/global-purchase-conditions'
import type { BreadcrumbItem } from '@/types'
import { Form, Head, Link } from '@inertiajs/vue3'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: conditionsIndex().url },
    { title: 'Global Purchase Conditions', href: conditionsIndex().url },
    { title: 'Create', href: GlobalPurchaseConditionController.create().url },
]
</script>

<template>
    <Head title="Create Global Purchase Condition" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <div>
                <Link :href="conditionsIndex().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Conditions
                </Link>
            </div>

            <Form v-bind="GlobalPurchaseConditionController.store.form()" class="space-y-8" v-slot="{ errors, processing }">
                <div class="space-y-4">
                    <Heading variant="small" title="Condition Details" description="Define a global purchase condition" />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" name="name" required placeholder="e.g. Terms & Conditions" />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input id="description" name="description" placeholder="Brief description (optional)" />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="content">Content (Rich Text / HTML)</Label>
                        <textarea
                            id="content"
                            name="content"
                            rows="6"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Full legal text or terms..."
                        />
                        <InputError :message="errors.content" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="acknowledgement_label">Acknowledgement Label</Label>
                        <Input id="acknowledgement_label" name="acknowledgement_label" required placeholder="e.g. I accept the Terms & Conditions" />
                        <InputError :message="errors.acknowledgement_label" />
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Settings" description="Configure behaviour" />

                    <div class="grid gap-2">
                        <Label for="sort_order">Sort Order</Label>
                        <Input id="sort_order" name="sort_order" type="number" min="0" :default-value="0" />
                        <InputError :message="errors.sort_order" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_required" name="is_required" :default-value="true" />
                        <Label for="is_required" class="cursor-pointer">Required</Label>
                    </div>
                    <InputError :message="errors.is_required" />

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_active" name="is_active" :default-value="true" />
                        <Label for="is_active" class="cursor-pointer">Active</Label>
                    </div>
                    <InputError :message="errors.is_active" />

                    <div class="flex items-center gap-2">
                        <Checkbox id="requires_scroll" name="requires_scroll" :default-value="false" />
                        <Label for="requires_scroll" class="cursor-pointer">Require scroll to bottom before accepting</Label>
                    </div>
                    <InputError :message="errors.requires_scroll" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create Condition' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
