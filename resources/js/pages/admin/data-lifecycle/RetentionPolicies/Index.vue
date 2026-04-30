<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

type Policy = {
    id: number;
    data_class: string;
    retention_days: number;
    legal_basis: string;
    description: string | null;
    can_be_force_deleted: boolean;
};

type EditPayload = {
    retention_days: number;
    legal_basis: string;
    description: string | null;
    can_be_force_deleted: boolean;
};

const props = defineProps<{ policies: Policy[] }>();

// Pre-create one Inertia form per policy at setup time. useForm() must run
// during the component's setup() pass so reactive bindings are tracked
// correctly, not lazily inside a render-phase helper.
const forms = new Map<number, ReturnType<typeof useForm<EditPayload>>>();

for (const p of props.policies) {
    forms.set(
        p.id,
        useForm<EditPayload>({
            retention_days: p.retention_days,
            legal_basis: p.legal_basis,
            description: p.description,
            can_be_force_deleted: p.can_be_force_deleted,
        }),
    );
}

const formFor = (id: number) => forms.get(id)!;

const save = (policy: Policy) => {
    formFor(policy.id).patch(
        `/admin/data-lifecycle/retention-policies/${policy.id}`,
        { preserveScroll: true },
    );
};
</script>

<template>
    <AppLayout>
        <Head title="Retention policies" />

        <div class="space-y-6 p-6">
            <Heading
                title="Retention policies"
                description="Per-data-class retention windows that govern how long deleted-user data is kept."
            />

            <div
                v-for="p in props.policies"
                :key="p.id"
                class="rounded border p-4"
            >
                <h3 class="font-mono text-sm">{{ p.data_class }}</h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ p.description }}
                </p>

                <form
                    class="mt-4 grid gap-3 md:grid-cols-2"
                    @submit.prevent="save(p)"
                >
                    <div class="grid gap-1">
                        <label class="text-xs"
                            >Retention days (0 = anonymize on deletion)</label
                        >
                        <input
                            v-model.number="formFor(p.id).retention_days"
                            type="number"
                            min="0"
                            max="36500"
                            class="rounded border p-2 text-sm"
                        />
                        <InputError
                            :message="formFor(p.id).errors.retention_days"
                        />
                    </div>

                    <div class="grid gap-1">
                        <label class="text-xs"
                            ><input
                                v-model="formFor(p.id).can_be_force_deleted"
                                type="checkbox"
                            />
                            Allow force-delete (admin override)</label
                        >
                    </div>

                    <div class="grid gap-1 md:col-span-2">
                        <label class="text-xs">Legal basis</label>
                        <textarea
                            v-model="formFor(p.id).legal_basis"
                            class="min-h-16 rounded border p-2 text-sm"
                        />
                        <InputError
                            :message="formFor(p.id).errors.legal_basis"
                        />
                    </div>

                    <div class="grid gap-1 md:col-span-2">
                        <label class="text-xs">Description</label>
                        <textarea
                            v-model="formFor(p.id).description"
                            class="min-h-16 rounded border p-2 text-sm"
                        />
                        <InputError
                            :message="formFor(p.id).errors.description"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <Button
                            type="submit"
                            :disabled="formFor(p.id).processing"
                        >
                            Save
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
