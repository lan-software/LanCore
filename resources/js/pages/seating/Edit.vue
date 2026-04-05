<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
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
import type { SeatPlan } from '@/types/domain';

const props = defineProps<{
    seatPlan: SeatPlan;
    events: { id: number; name: string }[];
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

const dataJson = JSON.stringify(props.seatPlan.data, null, 2);

function executeDelete() {
    router.delete(SeatPlanController.destroy(props.seatPlan.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Edit ${seatPlan.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="seatPlansRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Seat Plans
                </Link>
            </div>

            <Form
                v-bind="SeatPlanController.update.form(seatPlan.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Seat Plan Info -->
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
                            name="name"
                            :default-value="seatPlan.name"
                            required
                            placeholder="e.g. Main Hall"
                        />
                        <InputError :message="errors.name" />
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

                    <div class="grid gap-2">
                        <Label for="data">Seat Plan Data (JSON)</Label>
                        <Textarea
                            id="data"
                            name="data"
                            rows="20"
                            class="font-mono text-sm"
                            :default-value="dataJson"
                            placeholder='{"blocks": []}'
                        />
                        <p class="text-xs text-muted-foreground">
                            JSON describing blocks, seats, and labels for the
                            seat plan.
                        </p>
                        <InputError :message="errors.data" />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>

                    <p
                        v-if="recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >
                        Saved.
                    </p>
                </div>
            </Form>

            <!-- Delete section -->
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

        <!-- Delete confirmation dialog -->
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
    </AppLayout>
</template>
