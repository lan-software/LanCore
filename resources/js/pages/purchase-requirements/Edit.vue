<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import PurchaseRequirementController from '@/actions/App/Domain/Shop/Http/Controllers/PurchaseRequirementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as requirementsIndex } from '@/routes/purchase-requirements';
import type { BreadcrumbItem } from '@/types';

type PurchaseRequirement = {
    id: number;
    name: string;
    description: string | null;
    requirements_content: string | null;
    acknowledgements: string[] | null;
    is_active: boolean;
    requires_scroll: boolean;
    ticket_types: { id: number; name: string }[];
    addons: { id: number; name: string }[];
};

const props = defineProps<{
    requirement: PurchaseRequirement;
    ticketTypes: { id: number; name: string }[];
    addons: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: requirementsIndex().url },
    { title: 'Purchase Requirements', href: requirementsIndex().url },
    {
        title: 'Edit',
        href: PurchaseRequirementController.edit(props.requirement.id).url,
    },
];

const acknowledgements = ref<string[]>(
    props.requirement.acknowledgements?.length
        ? [...props.requirement.acknowledgements]
        : [''],
);

const selectedTicketTypeIds = ref<number[]>(
    props.requirement.ticket_types.map((t) => t.id),
);
const selectedAddonIds = ref<number[]>(
    props.requirement.addons.map((a) => a.id),
);

function addAcknowledgement() {
    acknowledgements.value.push('');
}

function removeAcknowledgement(index: number) {
    acknowledgements.value.splice(index, 1);
}

function toggleTicketType(id: number) {
    const idx = selectedTicketTypeIds.value.indexOf(id);

    if (idx === -1) {
        selectedTicketTypeIds.value.push(id);
    } else {
        selectedTicketTypeIds.value.splice(idx, 1);
    }
}

function toggleAddon(id: number) {
    const idx = selectedAddonIds.value.indexOf(id);

    if (idx === -1) {
        selectedAddonIds.value.push(id);
    } else {
        selectedAddonIds.value.splice(idx, 1);
    }
}

function deleteRequirement() {
    if (confirm('Are you sure you want to delete this purchase requirement?')) {
        router.delete(
            PurchaseRequirementController.destroy(props.requirement.id).url,
        );
    }
}
</script>

<template>
    <Head title="Edit Purchase Requirement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <div class="flex items-center justify-between">
                <Link
                    :href="requirementsIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Purchase Requirements
                </Link>
                <Button
                    variant="destructive"
                    size="sm"
                    @click="deleteRequirement"
                    >Delete</Button
                >
            </div>

            <Form
                v-bind="
                    PurchaseRequirementController.update.form(requirement.id)
                "
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Requirement Details"
                        description="Edit the purchase requirement"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            :default-value="requirement.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            name="description"
                            :default-value="requirement.description ?? ''"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="requirements_content"
                            >Content (Rich Text / HTML)</Label
                        >
                        <textarea
                            id="requirements_content"
                            name="requirements_content"
                            rows="6"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            :value="requirement.requirements_content ?? ''"
                        />
                        <InputError :message="errors.requirements_content" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            :default-value="requirement.is_active"
                        />
                        <Label for="is_active" class="cursor-pointer"
                            >Active</Label
                        >
                    </div>
                    <InputError :message="errors.is_active" />

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="requires_scroll"
                            name="requires_scroll"
                            :default-value="requirement.requires_scroll"
                        />
                        <Label for="requires_scroll" class="cursor-pointer"
                            >Require scroll to bottom before accepting</Label
                        >
                    </div>
                    <InputError :message="errors.requires_scroll" />
                </div>

                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Acknowledgements"
                        description="Checkboxes the buyer must accept"
                    />

                    <div
                        v-for="(_, index) in acknowledgements"
                        :key="index"
                        class="flex items-center gap-2"
                    >
                        <Input
                            v-model="acknowledgements[index]"
                            :name="`acknowledgements[${index}]`"
                            placeholder="e.g. I have read and accept the rules"
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
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addAcknowledgement"
                    >
                        <Plus class="size-4" />
                        Add Acknowledgement
                    </Button>
                </div>

                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Associations"
                        description="Select which items require this"
                    />

                    <div class="grid gap-2">
                        <Label>Ticket Types</Label>
                        <div
                            class="grid max-h-48 gap-1 overflow-y-auto rounded-md border p-3"
                        >
                            <div
                                v-for="tt in ticketTypes"
                                :key="tt.id"
                                class="flex items-center gap-2"
                            >
                                <Checkbox
                                    :id="`tt-${tt.id}`"
                                    :name="`ticket_type_ids[]`"
                                    :value="String(tt.id)"
                                    :default-value="
                                        selectedTicketTypeIds.includes(tt.id)
                                    "
                                    @update:checked="toggleTicketType(tt.id)"
                                />
                                <Label
                                    :for="`tt-${tt.id}`"
                                    class="cursor-pointer text-sm"
                                    >{{ tt.name }}</Label
                                >
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>Addons</Label>
                        <div
                            class="grid max-h-48 gap-1 overflow-y-auto rounded-md border p-3"
                        >
                            <div
                                v-for="addon in addons"
                                :key="addon.id"
                                class="flex items-center gap-2"
                            >
                                <Checkbox
                                    :id="`addon-${addon.id}`"
                                    :name="`addon_ids[]`"
                                    :value="String(addon.id)"
                                    :default-value="
                                        selectedAddonIds.includes(addon.id)
                                    "
                                    @update:checked="toggleAddon(addon.id)"
                                />
                                <Label
                                    :for="`addon-${addon.id}`"
                                    class="cursor-pointer text-sm"
                                    >{{ addon.name }}</Label
                                >
                            </div>
                        </div>
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
