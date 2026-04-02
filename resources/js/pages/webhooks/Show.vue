<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Pencil } from 'lucide-vue-next';
import { edit as webhookEdit } from '@/actions/App/Domain/Webhook/Http/Controllers/WebhookController';
import { show as webhookShow } from '@/actions/App/Domain/Webhook/Http/Controllers/WebhookController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as webhooksRoute } from '@/routes/webhooks';
import type { BreadcrumbItem } from '@/types';
import type { Webhook, WebhookDelivery } from '@/types/domain';

interface PaginatedDeliveries {
    data: WebhookDelivery[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    webhook: Webhook;
    deliveries: PaginatedDeliveries;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: webhooksRoute().url },
    { title: 'Webhooks', href: webhooksRoute().url },
    {
        title: props.webhook.name,
        href: webhookShow({ webhook: props.webhook.id }).url,
    },
];

const eventLabels: Record<string, string> = {
    'user.registered': 'User Registered',
    'announcement.published': 'Announcement Published',
    'news_article.published': 'News Article Published',
    'event.published': 'Event Published',
};

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function statusVariant(
    delivery: WebhookDelivery,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (delivery.succeeded) {
        return 'default';
    }

    if (delivery.status_code === null) {
        return 'secondary';
    }

    return 'destructive';
}

function statusLabel(delivery: WebhookDelivery): string {
    if (delivery.succeeded) {
        return 'Success';
    }

    if (delivery.status_code === null) {
        return 'Network Error';
    }

    return 'Failed';
}
</script>

<template>
    <Head :title="webhook.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-semibold">
                            {{ webhook.name }}
                        </h1>
                        <Badge v-if="webhook.is_active" variant="default"
                            >Active</Badge
                        >
                        <Badge v-else variant="outline">Inactive</Badge>
                        <Badge v-if="webhook.integration_app" variant="outline"
                            >Integration:
                            {{ webhook.integration_app.name }}</Badge
                        >
                    </div>
                    <div
                        class="flex items-center gap-3 text-sm text-muted-foreground"
                    >
                        <span class="font-mono">{{ webhook.url }}</span>
                        <span>&middot;</span>
                        <Badge variant="secondary">{{
                            eventLabels[webhook.event] ?? webhook.event
                        }}</Badge>
                        <span>&middot;</span>
                        <span
                            >{{
                                webhook.sent_count.toLocaleString()
                            }}
                            sent</span
                        >
                    </div>
                    <p
                        v-if="webhook.description"
                        class="mt-1 text-sm text-muted-foreground"
                    >
                        {{ webhook.description }}
                    </p>
                </div>
                <Link
                    v-if="!webhook.integration_app"
                    :href="webhookEdit({ webhook: webhook.id }).url"
                >
                    <Button variant="outline" size="sm">
                        <Pencil class="mr-1.5 size-3.5" />
                        Edit
                    </Button>
                </Link>
            </div>

            <!-- Deliveries table -->
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-medium">Delivery History</h2>
                    <span class="text-sm text-muted-foreground">
                        {{ deliveries.total.toLocaleString() }} deliveries
                    </span>
                </div>

                <Table class="border">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Fired At</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>HTTP Code</TableHead>
                            <TableHead>Duration</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="delivery in deliveries.data"
                            :key="delivery.id"
                        >
                            <TableCell class="text-sm">{{
                                formatDate(delivery.fired_at)
                            }}</TableCell>
                            <TableCell>
                                <Badge :variant="statusVariant(delivery)">{{
                                    statusLabel(delivery)
                                }}</Badge>
                            </TableCell>
                            <TableCell
                                class="text-muted-foreground tabular-nums"
                            >
                                {{ delivery.status_code ?? '—' }}
                            </TableCell>
                            <TableCell
                                class="text-muted-foreground tabular-nums"
                            >
                                {{
                                    delivery.duration_ms != null
                                        ? `${delivery.duration_ms} ms`
                                        : '—'
                                }}
                            </TableCell>
                        </TableRow>
                        <TableEmpty
                            v-if="deliveries.data.length === 0"
                            :columns-count="4"
                        >
                            No deliveries yet. Deliveries appear here each time
                            this webhook fires.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div
                    v-if="deliveries.last_page > 1"
                    class="flex items-center justify-between"
                >
                    <span class="text-sm text-muted-foreground">
                        {{ deliveries.from }}–{{ deliveries.to }} of
                        {{ deliveries.total }}
                    </span>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="deliveries.current_page === 1"
                            @click="
                                router.visit(
                                    webhookShow(
                                        { webhook: webhook.id },
                                        {
                                            query: {
                                                page:
                                                    deliveries.current_page - 1,
                                            },
                                        },
                                    ).url,
                                )
                            "
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                deliveries.current_page === deliveries.last_page
                            "
                            @click="
                                router.visit(
                                    webhookShow(
                                        { webhook: webhook.id },
                                        {
                                            query: {
                                                page:
                                                    deliveries.current_page + 1,
                                            },
                                        },
                                    ).url,
                                )
                            "
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
