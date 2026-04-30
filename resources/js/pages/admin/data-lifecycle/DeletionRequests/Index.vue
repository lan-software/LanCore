<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';

type DeletionRequest = {
    id: number;
    status: string;
    initiator: string;
    user_id: number;
    created_at: string;
    scheduled_for: string | null;
    user: {
        id: number;
        name: string | null;
        email: string | null;
        deleted_at: string | null;
    } | null;
};

defineProps<{
    requests: { data: DeletionRequest[] };
}>();

const statusBadge = (status: string): string => {
    return (
        {
            pending_email_confirm: 'bg-blue-100 text-blue-800',
            pending_grace: 'bg-yellow-100 text-yellow-800',
            anonymized: 'bg-green-100 text-green-800',
            cancelled: 'bg-neutral-100 text-neutral-800',
            force_deleted: 'bg-red-100 text-red-800',
        }[status] || 'bg-neutral-100 text-neutral-800'
    );
};
</script>

<template>
    <AppLayout>
        <Head title="Deletion requests" />

        <div class="space-y-6 p-6">
            <Heading
                title="Deletion requests"
                description="User and admin-induced GDPR Art. 17 deletion queue."
            />

            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="p-2">#</th>
                        <th class="p-2">User</th>
                        <th class="p-2">Initiator</th>
                        <th class="p-2">Status</th>
                        <th class="p-2">Scheduled</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="r in requests.data"
                        :key="r.id"
                        class="border-b hover:bg-neutral-50"
                    >
                        <td class="p-2 font-mono text-xs">{{ r.id }}</td>
                        <td class="p-2">
                            <span v-if="r.user">
                                {{ r.user.name || '(deleted)' }}
                                <span class="text-xs text-muted-foreground"
                                    >&lt;{{ r.user.email }}&gt;</span
                                >
                            </span>
                            <span v-else class="text-muted-foreground">
                                user #{{ r.user_id }}
                            </span>
                        </td>
                        <td class="p-2">{{ r.initiator }}</td>
                        <td class="p-2">
                            <span
                                class="rounded px-2 py-0.5 text-xs"
                                :class="statusBadge(r.status)"
                            >
                                {{ r.status }}
                            </span>
                        </td>
                        <td class="p-2 text-xs">
                            {{ r.scheduled_for || '—' }}
                        </td>
                        <td class="p-2">
                            <Link
                                :href="`/admin/data-lifecycle/deletion-requests/${r.id}`"
                                class="text-primary hover:underline"
                            >
                                View
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="requests.data.length === 0">
                        <td
                            colspan="6"
                            class="p-4 text-center text-muted-foreground"
                        >
                            No deletion requests yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
