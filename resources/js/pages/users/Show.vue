<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { currencyFromCode, formatCents } from '@/lib/money';
import { index as usersIndexRoute } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import type { Role, User } from '@/types/auth';
import type { Order, Ticket } from '@/types/domain';

const props = defineProps<{
    user: User;
    availableRoles: Role[];
    recentOrders: Order[];
    recentTickets: Ticket[];
}>();

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
