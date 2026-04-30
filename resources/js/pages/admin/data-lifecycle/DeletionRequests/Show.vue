<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

type DeletionRequest = {
    id: number;
    status: string;
    initiator: string;
    reason: string | null;
    scheduled_for: string | null;
    email_confirmed_at: string | null;
    anonymized_at: string | null;
    force_deleted_at: string | null;
    user_id: number;
    user: { id: number; name: string | null; email: string | null } | null;
};

type Verdict = {
    data_class: string;
    holds: boolean;
    until: string | null;
    basis: string;
};

const props = defineProps<{
    deletionRequest: DeletionRequest;
    retentionVerdicts: Verdict[];
}>();

const anonymizeForm = useForm({});
const cancelForm = useForm({});
const forceForm = useForm({ reason: '', confirmation: '' });

const anonymizeNow = () =>
    anonymizeForm.post(
        `/admin/data-lifecycle/deletion-requests/${props.deletionRequest.id}/anonymize-now`,
    );

const cancel = () =>
    cancelForm.post(
        `/admin/data-lifecycle/deletion-requests/${props.deletionRequest.id}/cancel`,
    );

const forceDelete = () => {
    if (!props.deletionRequest.user) {
        return;
    }

    forceForm.post(
        `/admin/data-lifecycle/users/${props.deletionRequest.user.id}/force-delete`,
        { preserveScroll: true },
    );
};
</script>

<template>
    <AppLayout>
        <Head :title="`Deletion request #${deletionRequest.id}`" />

        <div class="space-y-6 p-6">
            <Heading
                :title="`Deletion request #${deletionRequest.id}`"
                :description="`Status: ${deletionRequest.status}`"
            />

            <div class="grid gap-2 rounded border p-4 text-sm">
                <div>
                    <strong>User:</strong>
                    {{ deletionRequest.user?.name || '(anonymized)' }}
                    &lt;{{ deletionRequest.user?.email || '—' }}&gt;
                </div>
                <div>
                    <strong>Initiator:</strong>
                    {{ deletionRequest.initiator }}
                </div>
                <div v-if="deletionRequest.reason">
                    <strong>Reason:</strong>
                    {{ deletionRequest.reason }}
                </div>
                <div>
                    <strong>Scheduled for:</strong>
                    {{ deletionRequest.scheduled_for || '—' }}
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold">Retention verdicts</h3>
                <table class="mt-2 w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="p-2">Data class</th>
                            <th class="p-2">Hold?</th>
                            <th class="p-2">Until</th>
                            <th class="p-2">Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="v in retentionVerdicts"
                            :key="v.data_class"
                            class="border-b"
                        >
                            <td class="p-2 font-mono text-xs">
                                {{ v.data_class }}
                            </td>
                            <td class="p-2">{{ v.holds ? 'yes' : 'no' }}</td>
                            <td class="p-2 text-xs">{{ v.until || '—' }}</td>
                            <td class="p-2 text-xs">{{ v.basis }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="
                        deletionRequest.status === 'pending_email_confirm' ||
                        deletionRequest.status === 'pending_grace'
                    "
                    variant="outline"
                    @click="cancel"
                >
                    Cancel request
                </Button>
                <Button
                    v-if="deletionRequest.status === 'pending_grace'"
                    variant="destructive"
                    @click="anonymizeNow"
                >
                    Anonymize now (skip grace)
                </Button>
            </div>

            <div class="rounded border border-red-300 bg-red-50 p-4">
                <h3 class="text-base font-semibold text-red-900">
                    Force-delete user data
                </h3>
                <p class="mt-1 text-xs text-red-900">
                    Bypass retention windows and permanently remove the user row
                    plus all force-deletable data. This requires the
                    ForceDeleteUserData permission and is fully audited.
                </p>
                <form
                    v-if="deletionRequest.user"
                    class="mt-4 space-y-3"
                    @submit.prevent="forceDelete"
                >
                    <div class="grid gap-1">
                        <Label for="reason">Reason</Label>
                        <textarea
                            id="reason"
                            v-model="forceForm.reason"
                            class="min-h-20 rounded border p-2 text-sm"
                        />
                        <InputError :message="forceForm.errors.reason" />
                    </div>

                    <div class="grid gap-1">
                        <Label for="confirmation"
                            >Type "I UNDERSTAND THIS IS IRREVERSIBLE" to
                            confirm</Label
                        >
                        <input
                            id="confirmation"
                            v-model="forceForm.confirmation"
                            type="text"
                            class="rounded border p-2 text-sm"
                        />
                        <InputError :message="forceForm.errors.confirmation" />
                    </div>

                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="forceForm.processing"
                    >
                        Force-delete user data
                    </Button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
