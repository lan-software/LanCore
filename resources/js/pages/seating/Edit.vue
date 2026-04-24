<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SeatPlanController from '@/actions/App/Domain/Seating/Http/Controllers/SeatPlanController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as seatPlansRoute } from '@/routes/seat-plans';
import type { BreadcrumbItem } from '@/types';
import type { SeatPlan, SeatPlanData, TicketCategory } from '@/types/domain';
import BlockCategoryEditor from './partials/BlockCategoryEditor.vue';
import InvalidationConfirmDialog, {
    type InvalidationRow,
} from './partials/InvalidationConfirmDialog.vue';

const props = defineProps<{
    seatPlan: SeatPlan;
    events: { id: number; name: string }[];
    ticketCategories: Pick<TicketCategory, 'id' | 'name'>[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: seatPlansRoute().url },
    { title: 'Seat Plans', href: seatPlansRoute().url },
    {
        title: props.seatPlan.name,
        href: SeatPlanController.edit(props.seatPlan.id).url,
    },
];

const showDeleteDialog = ref(false);

function executeDelete() {
    router.delete(SeatPlanController.destroy(props.seatPlan.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}

/**
 * Structured-edit form.
 *
 * We DO NOT name the field `data` — Inertia's `useForm()` already exposes a
 * public `data()` method on the form instance, which shadows any field with
 * the same name and causes `v-model="form.data"` to resolve to the method's
 * source code instead of our string value. The field is renamed `dataJson`
 * here; `transform()` below maps it back to the server's `data` key.
 */
const form = useForm({
    name: props.seatPlan.name,
    dataJson: JSON.stringify(props.seatPlan.data, null, 2),
    confirm_invalidations: false as boolean,
}).transform((d) => ({
    name: d.name,
    data: d.dataJson,
    confirm_invalidations: d.confirm_invalidations,
}));

const parsedData = computed<SeatPlanData | null>({
    get() {
        try {
            return JSON.parse(form.dataJson) as SeatPlanData;
        } catch {
            return null;
        }
    },
    set(value) {
        if (value !== null) {
            form.dataJson = JSON.stringify(value, null, 2);
        }
    },
});

// Invalidations flashed from server when confirm_invalidations was false and
// the proposed change would orphan existing assignments (SET-F-012).
const page = usePage<{ flash: { invalidations?: InvalidationRow[] } }>();
const flaggedInvalidations = ref<InvalidationRow[]>([]);
const showInvalidationDialog = ref(false);

watch(
    () => page.props.flash?.invalidations,
    (rows) => {
        if (rows && rows.length > 0) {
            flaggedInvalidations.value = rows;
            showInvalidationDialog.value = true;
        }
    },
    { immediate: true, deep: true },
);

function submit(): void {
    form.patch(SeatPlanController.update.url(props.seatPlan.id), {
        preserveScroll: true,
    });
}

function confirmInvalidations(): void {
    form.confirm_invalidations = true;
    form.patch(SeatPlanController.update.url(props.seatPlan.id), {
        preserveScroll: true,
        onFinish: () => {
            form.confirm_invalidations = false;
            showInvalidationDialog.value = false;
            flaggedInvalidations.value = [];
        },
    });
}

function cancelInvalidationDialog(): void {
    showInvalidationDialog.value = false;
    flaggedInvalidations.value = [];
    form.confirm_invalidations = false;
}
</script>

<template>
    <Head :title="`Edit ${seatPlan.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-4xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="seatPlansRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Seat Plans
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Seat Plan Information"
                        description="Update the details for this seat plan"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="e.g. Main Hall"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Event</Label>
                        <Input
                            :default-value="seatPlan.event?.name ?? ''"
                            disabled
                        />
                        <p class="text-xs text-muted-foreground">
                            The event cannot be changed after creation.
                        </p>
                    </div>
                </div>

                <!-- Per-block category restriction editor (SET-F-011). Mutates
                     the same `data` string the textarea below exposes, so
                     admins can switch freely between structured + raw. -->
                <div v-if="parsedData" class="space-y-3">
                    <Heading
                        variant="small"
                        :title="$t('seating.admin.categoryEditorTitle')"
                        :description="
                            $t('seating.admin.categoryEditorDescription')
                        "
                    />
                    <BlockCategoryEditor
                        :data="parsedData"
                        :ticket-categories="ticketCategories"
                        @update:data="(d) => (parsedData = d)"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="dataJson">Seat Plan Data (JSON)</Label>
                    <Textarea
                        id="dataJson"
                        v-model="form.dataJson"
                        rows="20"
                        class="font-mono text-sm"
                        placeholder='{"blocks": []}'
                    />
                    <p class="text-xs text-muted-foreground">
                        JSON describing blocks, seats, labels, and per-block
                        <code>allowed_ticket_category_ids</code>.
                    </p>
                    <InputError :message="form.errors.data" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{
                            form.processing
                                ? $t('common.saving')
                                : 'Save Changes'
                        }}
                    </Button>

                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >
                        Saved.
                    </p>
                </div>
            </form>

            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">
                            Delete Seat Plan
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Permanently delete this seat plan and all its data.
                        </p>
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>
        </div>

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ seatPlan.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The seat plan and all its
                        data will be permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="executeDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Fires when the server reports invalidations on a non-confirmed
             save. Clicking Confirm re-submits with confirm_invalidations=true. -->
        <InvalidationConfirmDialog
            v-model:open="showInvalidationDialog"
            :invalidations="flaggedInvalidations"
            :processing="form.processing"
            @confirm="confirmInvalidations"
            @cancel="cancelInvalidationDialog"
        />
    </AppLayout>
</template>
