<script setup lang="ts">
import { Deferred, Form, Head, Link, useForm } from '@inertiajs/vue3';
import { Gamepad2 } from 'lucide-vue-next';
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
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { currencyFromCode, formatCents } from '@/lib/money';
import { index as usersIndexRoute } from '@/routes/users';
import { by as auditByRoute, on as auditOnRoute } from '@/routes/users/audits';
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

type AdminTicket = Ticket & { admin_role: 'owned' | 'managed' | 'assigned' };

type CommentRow = {
    id: number;
    content: string;
    is_approved: boolean;
    created_at: string;
    article: { id: number; title: string; slug: string } | null;
};

type AdminUser = User & {
    pending_deletion_at: string | null;
    anonymized_at: string | null;
    deleted_at: string | null;
    steam_status: 'linked' | 'steam_only' | 'not_linked';
    steam_id_64: string | null;
    steam_linked_at: string | null;
    profile_updated_at: string | null;
    phone: string | null;
    street: string | null;
    city: string | null;
    zip_code: string | null;
    country: string | null;
    short_bio: string | null;
    profile_description: string | null;
    profile_emoji: string | null;
    profile_visibility: 'public' | 'logged_in' | 'private' | null;
    is_ticket_discoverable: boolean;
    is_seat_visible_publicly: boolean;
};

const props = defineProps<{
    user: AdminUser;
    availableRoles: Role[];
    deletionRequests: DeletionRequestRow[];
    orders?: Order[];
    tickets?: AdminTicket[];
    comments?: CommentRow[];
}>();

const { can } = usePermissions();

const personalDataForm = useForm({
    phone: props.user.phone ?? '',
    street: props.user.street ?? '',
    city: props.user.city ?? '',
    zip_code: props.user.zip_code ?? '',
    country: props.user.country ?? '',
    short_bio: props.user.short_bio ?? '',
    profile_description: props.user.profile_description ?? '',
    profile_emoji: props.user.profile_emoji ?? '',
    profile_visibility: props.user.profile_visibility ?? 'logged_in',
    is_ticket_discoverable: props.user.is_ticket_discoverable,
    is_seat_visible_publicly: props.user.is_seat_visible_publicly,
});

const requestDeletionForm = useForm({
    user_id: props.user.id,
    reason: '',
});

const forceDeleteForm = useForm({
    reason: '',
    confirmation: '',
});

const anonymizeImmediatelyForm = useForm({
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
const showAnonymizeImmediately = ref(false);

const ANONYMIZE_CONFIRMATION_PHRASE =
    'I UNDERSTAND THIS SKIPS THE GRACE PERIOD';

const submitPersonalData = () => {
    personalDataForm.patch(
        UserController.updatePersonalData(props.user.id).url,
        {
            preserveScroll: true,
        },
    );
};

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

const submitAnonymizeImmediately = () => {
    if (
        anonymizeImmediatelyForm.confirmation !== ANONYMIZE_CONFIRMATION_PHRASE
    ) {
        return;
    }

    anonymizeImmediatelyForm.transform((data) => ({ reason: data.reason }));
    anonymizeImmediatelyForm.post(
        `/admin/data-lifecycle/users/${props.user.id}/anonymize-immediately`,
        {
            preserveScroll: true,
            onSuccess: () => {
                showAnonymizeImmediately.value = false;
                anonymizeImmediatelyForm.reset();
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

function formatDate(dateString: string | null): string {
    if (!dateString) {
return '—';
}

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

const ticketRoleVariant: Record<
    AdminTicket['admin_role'],
    'default' | 'secondary' | 'outline'
> = {
    owned: 'default',
    managed: 'secondary',
    assigned: 'outline',
};

const steamStatusMeta: Record<
    AdminUser['steam_status'],
    { label: string; variant: 'default' | 'secondary' | 'outline' }
> = {
    linked: { label: 'Linked', variant: 'default' },
    steam_only: { label: 'Steam-only', variant: 'secondary' },
    not_linked: { label: 'Not linked', variant: 'outline' },
};

const showLifecycleTab = computed(
    () =>
        can('request_user_deletion') ||
        can('force_delete_user_data') ||
        can('export_user_personal_data') ||
        props.deletionRequests.length > 0,
);

function truncate(value: string, length = 80): string {
    return value.length > length ? value.slice(0, length) + '…' : value;
}
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Back link + lifecycle banner -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="usersIndexRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Users
                </Link>

                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        :variant="steamStatusMeta[user.steam_status].variant"
                        class="gap-1"
                    >
                        <Gamepad2
                            v-if="user.steam_status !== 'not_linked'"
                            class="size-3"
                        />
                        {{ steamStatusMeta[user.steam_status].label }}
                    </Badge>

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
            </div>

            <Tabs default-value="profile">
                <TabsList class="self-start">
                    <TabsTrigger value="profile">Profile</TabsTrigger>
                    <TabsTrigger value="personal">Personal Data</TabsTrigger>
                    <TabsTrigger value="tickets">Tickets</TabsTrigger>
                    <TabsTrigger value="purchases">Purchases</TabsTrigger>
                    <TabsTrigger value="comments">Comments</TabsTrigger>
                    <TabsTrigger value="audit">Audit</TabsTrigger>
                    <TabsTrigger v-if="showLifecycleTab" value="lifecycle"
                        >Lifecycle</TabsTrigger
                    >
                </TabsList>

                <!-- Profile -->
                <TabsContent value="profile">
                    <Card>
                        <CardContent class="p-6">
                            <Form
                                v-bind="UserController.update.form(user.id)"
                                class="space-y-8"
                                v-slot="{
                                    errors,
                                    processing,
                                    recentlySuccessful,
                                }"
                            >
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

                                <div class="space-y-4">
                                    <Heading
                                        variant="small"
                                        title="Password"
                                        description="Leave blank to keep the current password"
                                    />
                                    <div class="grid gap-2">
                                        <Label for="password"
                                            >New password</Label
                                        >
                                        <Input
                                            id="password"
                                            type="password"
                                            name="password"
                                            autocomplete="new-password"
                                            placeholder="New password"
                                        />
                                        <InputError
                                            :message="errors.password"
                                        />
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
                                        <InputError
                                            :message="
                                                errors.password_confirmation
                                            "
                                        />
                                    </div>
                                </div>

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
                                        <InputError
                                            :message="errors.role_names"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <Button type="submit" :disabled="processing"
                                        >Save changes</Button
                                    >
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
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Personal Data -->
                <TabsContent value="personal">
                    <Card>
                        <CardHeader>
                            <CardTitle>Personal data</CardTitle>
                            <p
                                v-if="user.profile_updated_at"
                                class="text-xs text-muted-foreground"
                            >
                                Last updated
                                {{ formatDate(user.profile_updated_at) }}
                            </p>
                        </CardHeader>
                        <CardContent>
                            <form
                                class="space-y-4"
                                @submit.prevent="submitPersonalData"
                            >
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            v-model="personalDataForm.phone"
                                        />
                                        <InputError
                                            :message="
                                                personalDataForm.errors.phone
                                            "
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="country"
                                            >Country (ISO-2)</Label
                                        >
                                        <Input
                                            id="country"
                                            v-model="personalDataForm.country"
                                            maxlength="2"
                                        />
                                        <InputError
                                            :message="
                                                personalDataForm.errors.country
                                            "
                                        />
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="street">Street</Label>
                                    <Input
                                        id="street"
                                        v-model="personalDataForm.street"
                                    />
                                    <InputError
                                        :message="
                                            personalDataForm.errors.street
                                        "
                                    />
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="zip">ZIP / Postcode</Label>
                                        <Input
                                            id="zip"
                                            v-model="personalDataForm.zip_code"
                                        />
                                        <InputError
                                            :message="
                                                personalDataForm.errors.zip_code
                                            "
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="city">City</Label>
                                        <Input
                                            id="city"
                                            v-model="personalDataForm.city"
                                        />
                                        <InputError
                                            :message="
                                                personalDataForm.errors.city
                                            "
                                        />
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="bio">Short bio</Label>
                                    <Input
                                        id="bio"
                                        v-model="personalDataForm.short_bio"
                                        maxlength="255"
                                    />
                                    <InputError
                                        :message="
                                            personalDataForm.errors.short_bio
                                        "
                                    />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="profile-description"
                                        >Profile description</Label
                                    >
                                    <Textarea
                                        id="profile-description"
                                        v-model="
                                            personalDataForm.profile_description
                                        "
                                        rows="4"
                                    />
                                    <InputError
                                        :message="
                                            personalDataForm.errors
                                                .profile_description
                                        "
                                    />
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="emoji">Profile emoji</Label>
                                        <Input
                                            id="emoji"
                                            v-model="
                                                personalDataForm.profile_emoji
                                            "
                                            maxlength="8"
                                        />
                                        <InputError
                                            :message="
                                                personalDataForm.errors
                                                    .profile_emoji
                                            "
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="visibility"
                                            >Profile visibility</Label
                                        >
                                        <select
                                            id="visibility"
                                            v-model="
                                                personalDataForm.profile_visibility
                                            "
                                            class="h-9 rounded border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="public">
                                                Public
                                            </option>
                                            <option value="logged_in">
                                                Logged-in users
                                            </option>
                                            <option value="private">
                                                Private
                                            </option>
                                        </select>
                                        <InputError
                                            :message="
                                                personalDataForm.errors
                                                    .profile_visibility
                                            "
                                        />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="flex items-center gap-2">
                                        <input
                                            v-model="
                                                personalDataForm.is_ticket_discoverable
                                            "
                                            type="checkbox"
                                            class="size-4 rounded border border-input accent-primary"
                                        />
                                        <span class="text-sm"
                                            >Ticket discoverable by other
                                            attendees</span
                                        >
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input
                                            v-model="
                                                personalDataForm.is_seat_visible_publicly
                                            "
                                            type="checkbox"
                                            class="size-4 rounded border border-input accent-primary"
                                        />
                                        <span class="text-sm"
                                            >Seat name visible publicly</span
                                        >
                                    </label>
                                </div>

                                <div class="flex items-center gap-4">
                                    <Button
                                        type="submit"
                                        :disabled="personalDataForm.processing"
                                        >Save personal data</Button
                                    >
                                    <Transition
                                        enter-active-class="transition ease-in-out"
                                        enter-from-class="opacity-0"
                                        leave-active-class="transition ease-in-out"
                                        leave-to-class="opacity-0"
                                    >
                                        <p
                                            v-show="
                                                personalDataForm.recentlySuccessful
                                            "
                                            class="text-sm text-muted-foreground"
                                        >
                                            Saved.
                                        </p>
                                    </Transition>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tickets -->
                <TabsContent value="tickets">
                    <Deferred data="tickets">
                        <template #fallback>
                            <Card>
                                <CardContent class="space-y-2 p-4">
                                    <Skeleton class="h-8 w-full" />
                                    <Skeleton class="h-8 w-full" />
                                    <Skeleton class="h-8 w-2/3" />
                                </CardContent>
                            </Card>
                        </template>

                        <Card>
                            <CardContent class="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Ticket</TableHead>
                                            <TableHead>Role</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Event</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead class="text-right"
                                                >Actions</TableHead
                                            >
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <template
                                            v-if="tickets && tickets.length"
                                        >
                                            <TableRow
                                                v-for="ticket in tickets"
                                                :key="`${ticket.admin_role}-${ticket.id}`"
                                            >
                                                <TableCell
                                                    class="font-mono text-sm"
                                                    >#{{ ticket.id }}</TableCell
                                                >
                                                <TableCell>
                                                    <Badge
                                                        :variant="
                                                            ticketRoleVariant[
                                                                ticket
                                                                    .admin_role
                                                            ]
                                                        "
                                                        >{{
                                                            ticket.admin_role
                                                        }}</Badge
                                                    >
                                                </TableCell>
                                                <TableCell>{{
                                                    ticket.ticket_type?.name ??
                                                    '—'
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
                                                        >{{
                                                            ticket.status
                                                        }}</Badge
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
                                                                adminTicketShow(
                                                                    ticket.id,
                                                                ).url
                                                            "
                                                            >View</Link
                                                        >
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        </template>
                                        <TableEmpty v-else :colspan="6"
                                            >No tickets for this
                                            user.</TableEmpty
                                        >
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </Deferred>
                </TabsContent>

                <!-- Purchases -->
                <TabsContent value="purchases">
                    <Deferred data="orders">
                        <template #fallback>
                            <Card>
                                <CardContent class="space-y-2 p-4">
                                    <Skeleton class="h-8 w-full" />
                                    <Skeleton class="h-8 w-full" />
                                </CardContent>
                            </Card>
                        </template>

                        <Card>
                            <CardContent class="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>ID</TableHead>
                                            <TableHead>Event</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead class="text-right"
                                                >Total</TableHead
                                            >
                                            <TableHead>Date</TableHead>
                                            <TableHead class="text-right"
                                                >Actions</TableHead
                                            >
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <template
                                            v-if="orders && orders.length"
                                        >
                                            <TableRow
                                                v-for="order in orders"
                                                :key="order.id"
                                            >
                                                <TableCell
                                                    class="font-mono text-sm"
                                                    >#{{ order.id }}</TableCell
                                                >
                                                <TableCell>{{
                                                    order.event?.name ?? '—'
                                                }}</TableCell>
                                                <TableCell>
                                                    <Badge
                                                        :variant="
                                                            orderStatusVariant[
                                                                order.status
                                                            ] ?? 'outline'
                                                        "
                                                        >{{
                                                            order.status
                                                        }}</Badge
                                                    >
                                                </TableCell>
                                                <TableCell
                                                    class="text-right font-medium"
                                                    >{{
                                                        formatOrderCurrency(
                                                            order,
                                                        )
                                                    }}</TableCell
                                                >
                                                <TableCell
                                                    class="text-sm text-muted-foreground"
                                                    >{{
                                                        formatDate(
                                                            order.created_at,
                                                        )
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
                                                                OrderController.show(
                                                                    order.id,
                                                                ).url
                                                            "
                                                            >View</Link
                                                        >
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        </template>
                                        <TableEmpty v-else :colspan="6"
                                            >No orders for this
                                            user.</TableEmpty
                                        >
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </Deferred>
                </TabsContent>

                <!-- Comments -->
                <TabsContent value="comments">
                    <Deferred data="comments">
                        <template #fallback>
                            <Card>
                                <CardContent class="space-y-2 p-4">
                                    <Skeleton class="h-8 w-full" />
                                    <Skeleton class="h-8 w-full" />
                                </CardContent>
                            </Card>
                        </template>

                        <Card>
                            <CardContent class="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Article</TableHead>
                                            <TableHead>Comment</TableHead>
                                            <TableHead>Approved</TableHead>
                                            <TableHead>Posted</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <template
                                            v-if="comments && comments.length"
                                        >
                                            <TableRow
                                                v-for="comment in comments"
                                                :key="comment.id"
                                            >
                                                <TableCell
                                                    class="text-sm font-medium"
                                                    >{{
                                                        comment.article
                                                            ?.title ?? '—'
                                                    }}</TableCell
                                                >
                                                <TableCell
                                                    class="max-w-md text-xs"
                                                    >{{
                                                        truncate(
                                                            comment.content,
                                                            120,
                                                        )
                                                    }}</TableCell
                                                >
                                                <TableCell>
                                                    <Badge
                                                        :variant="
                                                            comment.is_approved
                                                                ? 'default'
                                                                : 'outline'
                                                        "
                                                        >{{
                                                            comment.is_approved
                                                                ? 'Approved'
                                                                : 'Pending'
                                                        }}</Badge
                                                    >
                                                </TableCell>
                                                <TableCell class="text-xs">{{
                                                    formatDate(
                                                        comment.created_at,
                                                    )
                                                }}</TableCell>
                                            </TableRow>
                                        </template>
                                        <TableEmpty v-else :colspan="4"
                                            >No comments by this
                                            user.</TableEmpty
                                        >
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </Deferred>
                </TabsContent>

                <!-- Audit -->
                <TabsContent value="audit">
                    <Card>
                        <CardHeader>
                            <CardTitle>Audit log</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-wrap gap-2">
                            <Button as-child variant="outline">
                                <Link :href="auditOnRoute(user.id).url"
                                    >View changes to this user</Link
                                >
                            </Button>
                            <Button as-child variant="outline">
                                <Link :href="auditByRoute(user.id).url"
                                    >View changes made by this user</Link
                                >
                            </Button>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Lifecycle -->
                <TabsContent v-if="showLifecycleTab" value="lifecycle">
                    <Card class="space-y-0">
                        <CardHeader>
                            <CardTitle>Data lifecycle</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm text-muted-foreground">
                                GDPR-compliant deletion, force-delete, and
                                export actions. All actions are audited.
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-if="
                                        can('request_user_deletion') &&
                                        !user.pending_deletion_at &&
                                        !user.anonymized_at
                                    "
                                    variant="outline"
                                    @click="
                                        showRequestDeletion =
                                            !showRequestDeletion
                                    "
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
                                    v-if="
                                        can('request_user_deletion') &&
                                        !user.pending_deletion_at &&
                                        !user.anonymized_at
                                    "
                                    variant="destructive"
                                    @click="
                                        showAnonymizeImmediately =
                                            !showAnonymizeImmediately
                                    "
                                >
                                    Anonymize immediately (skip email + grace)
                                </Button>

                                <Button
                                    v-if="can('force_delete_user_data')"
                                    variant="destructive"
                                    @click="showForceDelete = !showForceDelete"
                                >
                                    Force-delete (bypass retention)
                                </Button>
                            </div>

                            <form
                                v-if="showRequestDeletion"
                                class="space-y-3 rounded border p-4"
                                @submit.prevent="submitRequestDeletion"
                            >
                                <p class="text-sm">
                                    Opens a deletion request on behalf of this
                                    user. They'll receive an email confirmation;
                                    clicking the link starts a 30-day grace
                                    period.
                                </p>
                                <div class="grid gap-1">
                                    <Label for="dl-reason"
                                        >Reason (audited)</Label
                                    >
                                    <textarea
                                        id="dl-reason"
                                        v-model="requestDeletionForm.reason"
                                        class="min-h-20 rounded border p-2 text-sm"
                                        required
                                        minlength="5"
                                    />
                                    <InputError
                                        :message="
                                            requestDeletionForm.errors.reason
                                        "
                                    />
                                </div>
                                <div class="flex gap-2">
                                    <Button
                                        type="submit"
                                        :disabled="
                                            requestDeletionForm.processing
                                        "
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
                                    Generates a ZIP of every record held about
                                    this user and downloads it. Optional AES-256
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
                                    <Button type="submit"
                                        >Download export</Button
                                    >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        @click="showGdprExport = false"
                                        >Cancel</Button
                                    >
                                </div>
                            </form>

                            <form
                                v-if="showAnonymizeImmediately"
                                class="space-y-3 rounded border border-orange-300 bg-orange-50 p-4"
                                @submit.prevent="submitAnonymizeImmediately"
                            >
                                <p class="text-sm text-orange-900">
                                    <strong
                                        >Skips email confirmation and the grace
                                        period.</strong
                                    >
                                    Opens a deletion request, marks the
                                    confirmation as satisfied, and runs every
                                    domain anonymizer immediately.
                                    Per-data-class retention windows are still
                                    honoured (use Force-delete to bypass those).
                                    The reason is recorded in the audit trail.
                                </p>
                                <div class="grid gap-1">
                                    <Label for="ai-reason"
                                        >Reason (audited)</Label
                                    >
                                    <textarea
                                        id="ai-reason"
                                        v-model="
                                            anonymizeImmediatelyForm.reason
                                        "
                                        class="min-h-20 rounded border p-2 text-sm"
                                        required
                                        minlength="5"
                                    />
                                    <InputError
                                        :message="
                                            anonymizeImmediatelyForm.errors
                                                .reason
                                        "
                                    />
                                </div>
                                <div class="grid gap-1">
                                    <Label for="ai-confirm"
                                        >Type "{{
                                            ANONYMIZE_CONFIRMATION_PHRASE
                                        }}" to confirm</Label
                                    >
                                    <Input
                                        id="ai-confirm"
                                        v-model="
                                            anonymizeImmediatelyForm.confirmation
                                        "
                                        type="text"
                                    />
                                </div>
                                <div class="flex gap-2">
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="
                                            anonymizeImmediatelyForm.processing ||
                                            anonymizeImmediatelyForm.confirmation !==
                                                ANONYMIZE_CONFIRMATION_PHRASE
                                        "
                                        >Anonymize now</Button
                                    >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        @click="
                                            showAnonymizeImmediately = false
                                        "
                                        >Cancel</Button
                                    >
                                </div>
                            </form>

                            <form
                                v-if="showForceDelete"
                                class="space-y-3 rounded border border-red-300 bg-red-50 p-4"
                                @submit.prevent="submitForceDelete"
                            >
                                <p class="text-sm text-red-900">
                                    <strong>Irreversible.</strong> Bypasses
                                    retention windows and permanently removes
                                    the user row plus all force-deletable data.
                                    Use only for legal requests (court order,
                                    regulator demand). The reason is recorded in
                                    the audit trail.
                                </p>
                                <div class="grid gap-1">
                                    <Label for="fd-reason"
                                        >Reason (audited)</Label
                                    >
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
                                        >Type "I UNDERSTAND THIS IS
                                        IRREVERSIBLE" to confirm</Label
                                    >
                                    <Input
                                        id="fd-confirm"
                                        v-model="forceDeleteForm.confirmation"
                                        type="text"
                                    />
                                    <InputError
                                        :message="
                                            forceDeleteForm.errors.confirmation
                                        "
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

                    <Card v-if="deletionRequests.length" class="mt-4">
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
                                                :class="
                                                    statusBadgeClass(r.status)
                                                "
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
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
