<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { edit as eventEdit } from '@/actions/App/Domain/Event/Http/Controllers/EventController';
import OrderController from '@/actions/App/Domain/Shop/Http/Controllers/OrderController';
import { show as adminTicketShow } from '@/actions/App/Domain/Ticketing/Http/Controllers/AdminTicketController';
import { edit as ticketTypeEdit } from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketTypeController';
import { show as userShow } from '@/actions/App/Http/Controllers/Users/UserController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCents } from '@/lib/money';
import { index as adminTicketsIndex } from '@/routes/admin-tickets';
import type { BreadcrumbItem } from '@/types';
import type { Ticket } from '@/types/domain';

const { t } = useI18n();

interface ValidationToken {
    kid: string | null;
    issued_at: string | null;
    expires_at: string | null;
    status: 'Active' | 'Expired' | 'Revoked';
}

const props = defineProps<{
    ticket: Ticket;
    validation_token?: ValidationToken;
}>();

function rotateToken(): void {
    if (!confirm(t('adminTickets.rotateConfirm'))) {
        return;
    }

    router.post(
        `/admin-tickets/${props.ticket.id}/rotate-token`,
        {},
        { preserveScroll: true },
    );
}

const tokenStatusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    Active: 'default',
    Expired: 'secondary',
    Revoked: 'destructive',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('common.administration'), href: adminTicketsIndex().url },
    { title: t('navigation.tickets'), href: adminTicketsIndex().url },
    {
        title:
            props.ticket.ticket_type?.name ??
            t('adminTickets.ticketNumber', { id: props.ticket.id }),
        href: adminTicketShow(props.ticket.id).url,
    },
];

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    Active: 'default',
    CheckedIn: 'secondary',
    Cancelled: 'destructive',
};
</script>

<template>
    <Head
        :title="
            ticket.ticket_type?.name ??
            $t('adminTickets.ticketNumber', { id: ticket.id })
        "
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-4xl flex-1 flex-col gap-6 p-4">
            <div>
                <Link
                    :href="adminTicketsIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ $t('adminTickets.backToList') }}
                </Link>
            </div>

            <Heading
                :title="
                    ticket.ticket_type?.name ??
                    $t('adminTickets.ticketNumber', { id: ticket.id })
                "
                :description="
                    $t('adminTickets.ticketNumber', { id: ticket.id })
                "
            />

            <!-- Ticket Details -->
            <Card>
                <CardHeader>
                    <CardTitle>{{
                        $t('adminTickets.details.title')
                    }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{ $t('common.status') }}
                            </dt>
                            <dd class="mt-1">
                                <Badge
                                    :variant="
                                        statusVariant[ticket.status] ??
                                        'outline'
                                    "
                                    >{{ ticket.status }}</Badge
                                >
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{ $t('common.created') }}
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ formatDate(ticket.created_at) }}
                            </dd>
                        </div>
                        <div v-if="ticket.checked_in_at">
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{ $t('adminTickets.checkedInAt') }}
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ formatDate(ticket.checked_in_at) }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Validation Token -->
            <Card v-if="validation_token">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>{{
                            $t('adminTickets.validationToken.title')
                        }}</CardTitle>
                        <Badge
                            :variant="
                                tokenStatusVariant[validation_token.status] ??
                                'outline'
                            "
                        >
                            {{ validation_token.status }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{ $t('adminTickets.validationToken.keyId') }}
                            </dt>
                            <dd class="mt-1 font-mono text-sm">
                                {{ validation_token.kid ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{
                                    $t('adminTickets.validationToken.issuedAt')
                                }}
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    validation_token.issued_at
                                        ? formatDate(validation_token.issued_at)
                                        : '—'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{
                                    $t('adminTickets.validationToken.expiresAt')
                                }}
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    validation_token.expires_at
                                        ? formatDate(
                                              validation_token.expires_at,
                                          )
                                        : '—'
                                }}
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-4">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="rotateToken"
                        >
                            {{ $t('adminTickets.validationToken.rotate') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Ticket Type -->
            <Card v-if="ticket.ticket_type">
                <CardHeader>
                    <CardTitle>{{
                        $t('adminTickets.ticketType.title')
                    }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">
                                {{ ticket.ticket_type.name }}
                            </p>
                            <p
                                v-if="ticket.ticket_type.ticket_category"
                                class="text-sm text-muted-foreground"
                            >
                                {{
                                    $t('adminTickets.ticketType.category', {
                                        name: ticket.ticket_type.ticket_category
                                            .name,
                                    })
                                }}
                            </p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link
                                :href="
                                    ticketTypeEdit(ticket.ticket_type_id).url
                                "
                                >{{ $t('adminTickets.ticketType.view') }}</Link
                            >
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Event -->
            <Card v-if="ticket.event">
                <CardHeader>
                    <CardTitle>{{ $t('common.event') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <p class="font-medium">{{ ticket.event.name }}</p>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="eventEdit(ticket.event_id).url">{{
                                $t('adminTickets.event.view')
                            }}</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Order -->
            <Card v-if="ticket.order">
                <CardHeader>
                    <CardTitle>{{ $t('common.order') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">
                                {{
                                    $t('adminTickets.order.number', {
                                        id: ticket.order.id,
                                    })
                                }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ ticket.order.user?.name }} &middot;
                                {{ ticket.order.status }}
                            </p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link
                                :href="
                                    OrderController.show(ticket.order_id).url
                                "
                                >{{ $t('adminTickets.order.view') }}</Link
                            >
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Owner -->
            <Card v-if="ticket.owner">
                <CardHeader>
                    <CardTitle>{{ $t('adminTickets.owner.title') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ ticket.owner.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ ticket.owner.email }}
                            </p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="userShow(ticket.owner_id).url">{{
                                $t('adminTickets.viewUser')
                            }}</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Manager -->
            <Card v-if="ticket.manager">
                <CardHeader>
                    <CardTitle>{{
                        $t('adminTickets.manager.title')
                    }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ ticket.manager.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ ticket.manager.email }}
                            </p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="userShow(ticket.manager.id).url">{{
                                $t('adminTickets.viewUser')
                            }}</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Assigned Users -->
            <Card v-if="ticket.users && ticket.users.length > 0">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <CardTitle>{{
                            $t('adminTickets.assignedUsers.title')
                        }}</CardTitle>
                        <Badge
                            v-if="
                                ticket.ticket_type &&
                                ticket.ticket_type.max_users_per_ticket > 1
                            "
                            variant="outline"
                        >
                            {{ ticket.users.length }}/{{
                                ticket.ticket_type.max_users_per_ticket
                            }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="user in ticket.users"
                            :key="user.id"
                            class="flex items-center justify-between"
                        >
                            <div>
                                <p class="font-medium">{{ user.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ user.email }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge
                                    v-if="user.pivot?.checked_in_at"
                                    variant="secondary"
                                >
                                    {{ $t('common.checkedIn') }}
                                </Badge>
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="userShow(user.id).url">{{
                                        $t('adminTickets.viewUser')
                                    }}</Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Addons -->
            <Card v-if="ticket.addons && ticket.addons.length > 0">
                <CardHeader>
                    <CardTitle>{{ $t('common.addons') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-2">
                        <li
                            v-for="addon in ticket.addons"
                            :key="addon.id"
                            class="flex items-center justify-between"
                        >
                            <span class="font-medium">{{ addon.name }}</span>
                            <span class="text-sm text-muted-foreground">{{
                                formatCents(addon.price)
                            }}</span>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
