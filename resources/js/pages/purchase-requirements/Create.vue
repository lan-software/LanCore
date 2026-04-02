<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3'
import { Plus, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import PurchaseRequirementController from '@/actions/App/Domain/Shop/Http/Controllers/PurchaseRequirementController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as requirementsIndex } from '@/routes/purchase-requirements'
import type { BreadcrumbItem } from '@/types'

defineProps<{
    ticketTypes: { id: number; name: string }[]
    addons: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: requirementsIndex().url },
    { title: 'Purchase Requirements', href: requirementsIndex().url },
    { title: 'Create', href: PurchaseRequirementController.create().url },
]

const acknowledgements = ref<string[]>([''])

function addAcknowledgement() {
    acknowledgements.value.push('')
}

function removeAcknowledgement(index: number) {
    acknowledgements.value.splice(index, 1)
}
</script>

<template>
    <Head title="Create Purchase Requirement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <div>
                <Link :href="requirementsIndex().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Purchase Requirements
                </Link>
            </div>

            <Form v-bind="PurchaseRequirementController.store.form()" class="space-y-8" v-slot="{ errors, processing }">
                <div class="space-y-4">
                    <Heading variant="small" title="Requirement Details" description="Define the purchase requirement" />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" name="name" required placeholder="e.g. Tournament Rules Agreement" />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input id="description" name="description" placeholder="Brief description (optional)" />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="requirements_content">Content (Rich Text / HTML)</Label>
                        <textarea
                            id="requirements_content"
                            name="requirements_content"
                            rows="6"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Full text content shown to the buyer before purchase..."
                        />
                        <InputError :message="errors.requirements_content" />
                    </div>

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

                <div class="space-y-4">
                    <Heading variant="small" title="Acknowledgements" description="Checkboxes the buyer must accept before purchase" />

                    <div v-for="(_, index) in acknowledgements" :key="index" class="flex items-center gap-2">
                        <Input
                            v-model="acknowledgements[index]"
                            :name="`acknowledgements[${index}]`"
                            placeholder="e.g. I have read and accept the tournament rules"
                            class="flex-1"
                        />
                        <Button
                            v-if="acknowledgements.length > 1"
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-8 text-muted-foreground hover:text-destructive"
                            @click="removeAcknowledgement(index)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addAcknowledgement">
                        <Plus class="size-4" />
                        Add Acknowledgement
                    </Button>
                    <InputError :message="errors['acknowledgements']" />
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Associations" description="Select which items require this" />

                    <div class="grid gap-2">
                        <Label>Ticket Types</Label>
                        <div class="grid gap-1 max-h-48 overflow-y-auto rounded-md border p-3">
                            <div v-for="tt in ticketTypes" :key="tt.id" class="flex items-center gap-2">
                                <Checkbox :id="`tt-${tt.id}`" :name="`ticket_type_ids[]`" :value="String(tt.id)" />
                                <Label :for="`tt-${tt.id}`" class="cursor-pointer text-sm">{{ tt.name }}</Label>
                            </div>
                            <p v-if="ticketTypes.length === 0" class="text-sm text-muted-foreground">No ticket types available.</p>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>Addons</Label>
                        <div class="grid gap-1 max-h-48 overflow-y-auto rounded-md border p-3">
                            <div v-for="addon in addons" :key="addon.id" class="flex items-center gap-2">
                                <Checkbox :id="`addon-${addon.id}`" :name="`addon_ids[]`" :value="String(addon.id)" />
                                <Label :for="`addon-${addon.id}`" class="cursor-pointer text-sm">{{ addon.name }}</Label>
                            </div>
                            <p v-if="addons.length === 0" class="text-sm text-muted-foreground">No addons available.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create Requirement' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
