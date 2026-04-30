<script setup lang="ts">
import { Form, Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import OrderController from '@/actions/App/Domain/Shop/Http/Controllers/OrderController';
import { show as adminTicketShow } from '@/actions/App/Domain/Ticketing/Http/Controllers/AdminTicketController';
import UserController from '@/actions/App/Http/Controllers/Users/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { currencyFromCode, formatCents } from '@/lib/money';
import { index as usersIndexRoute } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import type { Role, User } from '@/types/auth';
import type { Order, Ticket } from '@/types/domain';

type DeletionRequestRow = {
    id: number;
    status: string;
    initiator: string;
    reason: string | null;
    scheduled_for: string | null;
    anonymized_at: string | null;
    force_deleted_at: string | null;
    cancelled_at: string | null;
    created_at: string;
};

type AdminUser = User & {
    pending_deletion_at: string | null;
    anonymized_at: string | null;
    deleted_at: string | null;
};

const props = defineProps<{
    user: AdminUser;
    availableRoles: Role[];
    recentOrders: Order[];
    recentTickets: Ticket[];
    deletionRequests: DeletionRequestRow[];
}>();

const { can } = usePermissions();

const requestDeletionForm = useForm({
    user_id: props.user.id,
    reason: '',
});

const forceDeleteForm = useForm({
    reason: '',
    confirmation: '',
});

const gdprExportPassword = ref('');
const gdprExportIncludeSoftDeleted = ref(true);

const csrfToken = computed<string>(
    () =>
        document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '',
);

const showRequestDeletion = ref(false);
const showForceDelete = ref(false);
const showGdprExport = ref(false);

const submitRequestDeletion = () => {
    requestDeletionForm.post('/admin/data-lifecycle/deletion-requests', {
        preserveScroll: true,
        onSuccess: () => {
            showRequestDeletion.value = false;
            requestDeletionForm.reset('reason');
        },
    });
};

const submitForceDelete = () => {
    forceDeleteForm.post(
        `/admin/data-lifecycle/users/${props.user.id}/force-delete`,
        {
            preserveScroll: true,
            onSuccess: () => {
                showForceDelete.value = false;
                forceDeleteForm.reset();
            },
        },
    );
};

const statusBadgeClass = (status: string): string =>
    ({
        pending_email_confirm: 'bg-blue-100 text-blue-800',
        pending_grace: 'bg-yellow-100 text-yellow-800',
        anonymized: 'bg-green-100 text-green-800',
        cancelled: 'bg-neutral-100 text-neutral-800',
        force_deleted: 'bg-red-100 text-red-800',
    })[status] || 'bg-neutral-100 text-neutral-800';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: usersIndexRoute().url },
    { title: 'Users', href: usersIndexRoute().url },
    { title: props.user.name, href: UserController.show(props.user.id).url },
];

function hasRole(roleName: string): boolean {
    return props.user.roles.some((r) => r.name === roleName);
}

function formatOrderCurrency(order: Order): string {
    return formatCents(order.total, currencyFromCode(order.currency));
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

const orderStatusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    Completed: 'default',
    Pending: 'outline',
    Failed: 'destructive',
    Refunded: 'secondary',
};

const ticketStatusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    Active: 'default',
    CheckedIn: 'secondary',
    Cancelled: 'destructive',
};
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="usersIndexRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Users
                </Link>
            </div>

            <Form
                v-bind="UserController.update.form(user.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Profile section -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Profile"
                        description="Update the user's name and email address"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="email"
                            placeholder="Email address"
                        />
                        <InputError :message="errors.email" />
                    </div>
                </div>

                <!-- Password section -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Password"
                        description="Leave blank to keep the current password"
                    />

                    <div class="grid gap-2">
                        <Label for="password">New password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            placeholder="New password"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Confirm password</Label
                        >
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="Confirm new password"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>
                </div>

                <!-- Roles section -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Roles"
                        description="Assign roles to this user"
                    />

                    <div class="space-y-2">
                        <div
                            v-for="role in availableRoles"
                            :key="role.id"
                            class="flex items-center gap-2"
                        >
                            <input
                                type="checkbox"
                                :id="`role-${role.id}`"
                                name="role_names[]"
                                :value="role.name"
                                :checked="hasRole(role.name)"
                                class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                            />
                            <Label
                                :for="`role-${role.id}`"
                                class="cursor-pointer"
                            >
                                {{ role.label }}
                            </Label>
                        </div>
                        <InputError :message="errors.role_names" />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-muted-foreground"
                        >
                            Saved.
                        </p>
                    </Transition>
                </div>
            </Form>

            <!-- Recent Orders -->
            <Card v-if="recentOrders.length > 0">
                <CardHeader>
                    <CardTitle>Recent Orders</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Total</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead class="text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="order in recentOrders"
                                :key="order.id"
                            >
                                <TableCell class="font-mono text-sm"
                                    >#{{ order.id }}</TableCell
                                >
                                <TableCell>{{
                                    order.event?.name ?? '—'
                                }}</TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            orderStatusVariant[order.status] ??
                                            'outline'
                                        "
                                        >{{ order.status }}</Badge
                                    >
                                </TableCell>
                                <TableCell class="text-right font-medium">{{
                                    formatOrderCurrency(order)
                                }}</TableCell>
                                <TableCell
                                    class="text-sm text-muted-foreground"
                                    >{{
                                        formatDate(order.created_at)
                                    }}</TableCell
                                >
                                <TableCell class="text-right">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                OrderController.show(order.id)
                                                    .url
                                            "
                                            >View</Link
                                        >
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Account lifecycle status -->
            <div v-if="user.pending_deletion_at || user.anonymized_at || user.deleted_at" class="space-y-1">
                <Badge
                    v-if="user.anonymized_at"
                    variant="destructive"
                    class="text-xs"
                >
                    Anonymized {{ formatDate(user.anonymized_at) }}
                </Badge>
                <Badge
                    v-else-if="user.pending_deletion_at"
                    variant="outline"
                    class="text-xs"
                >
                    Pending deletion since
                    {{ formatDate(user.pending_deletion_at) }}
                </Badge>
                <Badge
                    v-if="user.deleted_at"
                    variant="secondary"
                    class="text-xs"
                >
                    Soft-deleted {{ formatDate(user.deleted_at) }}
                </Badge>
            </div>

            <!-- Data Lifecycle actions -->
            <Card
                v-if="
                    can('request_user_deletion') ||
                    can('force_delete_user_data') ||
                    can('export_user_personal_data')
                "
            >
                <CardHeader>
                    <CardTitle>Data lifecycle</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-sm text-muted-foreground">
                        GDPR-compliant deletion, force-delete, and export
                        actions. All actions are audited.
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="
                                can('request_user_deletion') &&
                                !user.pending_deletion_at &&
                                !user.anonymized_at
                            "
                            variant="outline"
                            @click="showRequestDeletion = !showRequestDeletion"
                        >
                            Request deletion (GDPR flow)
                        </Button>

                        <Button
                            v-if="
                                can('export_user_personal_data') &&
                                !user.anonymized_at
                            "
                            variant="outline"
                            @click="showGdprExport = !showGdprExport"
                        >
                            Trigger GDPR Art.15 export
                        </Button>

                        <Button
                            v-if="can('force_delete_user_data')"
                            variant="destructive"
                            @click="showForceDelete = !showForceDelete"
                        >
                            Force-delete (bypass retention)
                        </Button>
                    </div>

                    <!-- Request deletion form -->
                    <form
                        v-if="showRequestDeletion"
                        class="space-y-3 rounded border p-4"
                        @submit.prevent="submitRequestDeletion"
                    >
                        <p class="text-sm">
                            Opens a deletion request on behalf of this user.
                            They'll receive an email confirmation; clicking the
                            link starts a 30-day grace period.
                        </p>
                        <div class="grid gap-1">
                            <Label for="dl-reason">Reason (audited)</Label>
                            <textarea
                                id="dl-reason"
                                v-model="requestDeletionForm.reason"
                                class="min-h-20 rounded border p-2 text-sm"
                                required
                                minlength="5"
                            />
                            <InputError
                                :message="requestDeletionForm.errors.reason"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="submit"
                                :disabled="requestDeletionForm.processing"
                                >Submit request</Button
                            >
                            <Button
                                type="button"
                                variant="ghost"
                                @click="showRequestDeletion = false"
                                >Cancel</Button
                            >
                        </div>
                    </form>

                    <!--
                        GDPR export uses a native form submit (not Inertia's
                        useForm) because the controller streams a binary ZIP
                        back; Inertia's XHR-based form helpers expect an
                        Inertia/redirect response and can't trigger a download.
                    -->
                    <form
                        v-if="showGdprExport"
                        :action="`/admin/data-lifecycle/users/${user.id}/gdpr-export`"
                        method="POST"
                        target="_blank"
                        class="space-y-3 rounded border p-4"
                    >
                        <input
                            type="hidden"
                            name="_token"
                            :value="csrfToken"
                        />
                        <p class="text-sm">
                            Generates a ZIP of every record held about this
                            user and downloads it. Optional AES-256
                            password-protection.
                        </p>
                        <div class="grid gap-1">
                            <Label for="dl-export-pwd"
                                >ZIP password (optional)</Label
                            >
                            <Input
                                id="dl-export-pwd"
                                v-model="gdprExportPassword"
                                name="password"
                                type="password"
                                autocomplete="off"
                            />
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="gdprExportIncludeSoftDeleted"
                                type="checkbox"
                                name="include_soft_deleted"
                                value="1"
                            />
                            Include soft-deleted user data
                        </label>
                        <div class="flex gap-2">
                            <Button type="submit">Download export</Button>
                            <Button
                                type="button"
                                variant="ghost"
                                @click="showGdprExport = false"
                                >Cancel</Button
                            >
                        </div>
                    </form>

                    <!-- Force delete form -->
                    <form
                        v-if="showForceDelete"
                        class="space-y-3 rounded border border-red-300 bg-red-50 p-4"
                        @submit.prevent="submitForceDelete"
                    >
                        <p class="text-sm text-red-900">
                            <strong>Irreversible.</strong> Bypasses retention
                            windows and permanently removes the user row plus
                            all force-deletable data. Use only for legal
                            requests (court order, regulator demand). The
                            reason is recorded in the audit trail.
                        </p>
                        <div class="grid gap-1">
                            <Label for="fd-reason">Reason (audited)</Label>
                            <textarea
                                id="fd-reason"
                                v-model="forceDeleteForm.reason"
                                class="min-h-24 rounded border p-2 text-sm"
                                required
                                minlength="10"
                            />
                            <InputError
                                :message="forceDeleteForm.errors.reason"
                            />
                        </div>
                        <div class="grid gap-1">
                            <Label for="fd-confirm"
                                >Type "I UNDERSTAND THIS IS IRREVERSIBLE" to
                                confirm</Label
                            >
                            <Input
                                id="fd-confirm"
                                v-model="forceDeleteForm.confirmation"
                                type="text"
                            />
                            <InputError
                                :message="forceDeleteForm.errors.confirmation"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="forceDeleteForm.processing"
                                >Force-delete now</Button
                            >
                            <Button
                                type="button"
                                variant="ghost"
                                @click="showForceDelete = false"
                                >Cancel</Button
                            >
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Deletion request history -->
            <Card v-if="deletionRequests.length > 0">
                <CardHeader>
                    <CardTitle>Deletion request history</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>#</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Initiator</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead>Scheduled</TableHead>
                                <TableHead>Created</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="r in deletionRequests"
                                :key="r.id"
                            >
                                <TableCell class="font-mono text-xs"
                                    >#{{ r.id }}</TableCell
                                >
                                <TableCell>
                                    <span
                                        class="rounded px-2 py-0.5 text-xs"
                                        :class="statusBadgeClass(r.status)"
                                        >{{ r.status }}</span
                                    >
                                </TableCell>
                                <TableCell class="text-sm">{{
                                    r.initiator
                                }}</TableCell>
                                <TableCell
                                    class="max-w-md truncate text-xs"
                                    :title="r.reason ?? ''"
                                    >{{ r.reason || '—' }}</TableCell
                                >
                                <TableCell class="text-xs">{{
                                    r.scheduled_for
                                        ? formatDate(r.scheduled_for)
                                        : '—'
                                }}</TableCell>
                                <TableCell class="text-xs">{{
                                    formatDate(r.created_at)
                                }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Recent Tickets -->
            <Card v-if="recentTickets.length > 0">
                <CardHeader>
                    <CardTitle>Recent Tickets</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Ticket</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="ticket in recentTickets"
                                :key="ticket.id"
                            >
                                <TableCell class="font-mono text-sm"
                                    >#{{ ticket.id }}</TableCell
                                >
                                <TableCell>{{
                                    ticket.ticket_type?.name ?? '—'
                                }}</TableCell>
                                <TableCell>{{
                                    ticket.event?.name ?? '—'
                                }}</TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            ticketStatusVariant[
                                                ticket.status
                                            ] ?? 'outline'
                                        "
                                        >{{ ticket.status }}</Badge
                                    >
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                adminTicketShow(ticket.id).url
                                            "
                                            >View</Link
                                        >
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
