<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TicketController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketController';
import TicketCard from '@/components/TicketCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as ticketsIndex } from '@/routes/tickets';
import type { BreadcrumbItem } from '@/types';
import type { Ticket } from '@/types/domain';

const props = defineProps<{
    ticket: Ticket;
    canUpdateManager: boolean;
    canUpdateUser: boolean;
    canRotateToken: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Tickets', href: ticketsIndex().url },
    {
        title: props.ticket.ticket_type?.name ?? `Ticket #${props.ticket.id}`,
        href: TicketController.show(props.ticket.id).url,
    },
];
</script>

<template>
    <Head :title="ticket.ticket_type?.name ?? `Ticket #${ticket.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-xl flex-1 flex-col gap-6 p-4">
            <div>
                <Link
                    :href="ticketsIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to My Tickets
                </Link>
            </div>

            <TicketCard
                :ticket="ticket"
                :can-update-manager="canUpdateManager"
                :can-update-user="canUpdateUser"
                :can-rotate-token="canRotateToken"
            />
        </div>
    </AppLayout>
</template>
