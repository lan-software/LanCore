<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingCart } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import EventSelector from '@/components/EventSelector.vue';
import TicketCard from '@/components/TicketCard.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as shopIndex } from '@/routes/shop';
import { index as ticketsIndex } from '@/routes/tickets';
import type { BreadcrumbItem } from '@/types';
import type { Ticket } from '@/types/domain';

defineProps<{
    ownedTickets: Ticket[];
    managedTickets: Ticket[];
    assignedTickets: Ticket[];
}>();

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('tickets.title'), href: ticketsIndex().url },
];
</script>

<template>
    <Head :title="t('tickets.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ t('tickets.title') }}</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ t('tickets.description') }}
                    </p>
                </div>
                <Button as-child>
                    <Link :href="shopIndex().url">
                        <ShoppingCart class="size-4" />
                        {{ t('tickets.buyTickets') }}
                    </Link>
                </Button>
            </div>

            <EventSelector variant="my" :sidebar="false" />

            <!-- Owned Tickets -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">{{ t('tickets.owned') }}</h2>
                <div
                    v-if="ownedTickets.length > 0"
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <TicketCard
                        v-for="ticket in ownedTickets"
                        :key="ticket.id"
                        :ticket="ticket"
                        :can-update-manager="true"
                        :can-update-user="true"
                    />
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('tickets.noOwned') }}
                </p>
            </div>

            <!-- Managed Tickets -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">{{ t('tickets.managed') }}</h2>
                <div
                    v-if="managedTickets.length > 0"
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <TicketCard
                        v-for="ticket in managedTickets"
                        :key="ticket.id"
                        :ticket="ticket"
                        :can-update-user="true"
                    />
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('tickets.noManaged') }}
                </p>
            </div>

            <!-- Usable Tickets -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">{{ t('tickets.assigned') }}</h2>
                <div
                    v-if="assignedTickets.length > 0"
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <TicketCard
                        v-for="ticket in assignedTickets"
                        :key="ticket.id"
                        :ticket="ticket"
                    />
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('tickets.noAssigned') }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
