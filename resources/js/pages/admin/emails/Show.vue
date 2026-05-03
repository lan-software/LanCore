<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as emailsIndexRoute } from '@/routes/admin/emails';
import type { BreadcrumbItem } from '@/types';

type Address = { address: string; name: string | null };

type Message = {
    id: number;
    message_id: string | null;
    mailer: string | null;
    from_address: string | null;
    from_name: string | null;
    to_addresses: Address[] | null;
    cc_addresses: Address[] | null;
    bcc_addresses: Address[] | null;
    subject: string | null;
    html_body: string | null;
    text_body: string | null;
    headers: Record<string, string> | null;
    status: 'queued' | 'sent' | 'failed' | 'bounced' | 'complained';
    error: string | null;
    source: string | null;
    source_label: string | null;
    sent_at: string | null;
    failed_at: string | null;
    created_at: string;
};

const props = defineProps<{
    message: Message;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: emailsIndexRoute().url },
    { title: 'Emails', href: emailsIndexRoute().url },
    {
        title: props.message.subject ?? `#${props.message.id}`,
        href: '#',
    },
];

const statusVariant: Record<
    Message['status'],
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    queued: 'outline',
    sent: 'default',
    failed: 'destructive',
    bounced: 'destructive',
    complained: 'destructive',
};

function formatAddresses(addrs: Address[] | null): string {
    if (!addrs || addrs.length === 0) {
        return '—';
    }

    return addrs
        .map((a) => (a.name ? `${a.name} <${a.address}>` : a.address))
        .join(', ');
}

function shortSource(source: string | null): string {
    if (!source) {
        return '—';
    }

    return source.split('\\').pop() ?? source;
}
</script>

<template>
    <Head :title="message.subject ?? `Email #${message.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Badge :variant="statusVariant[message.status]">{{
                        message.status
                    }}</Badge>
                    <h1 class="text-lg font-semibold">
                        {{ message.subject ?? '(no subject)' }}
                    </h1>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="emailsIndexRoute().url">Back to list</Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Metadata</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid gap-x-4 gap-y-2 text-sm sm:grid-cols-[140px_1fr]"
                >
                    <div class="text-muted-foreground">Sent</div>
                    <div>{{ message.sent_at ?? message.created_at }}</div>

                    <div class="text-muted-foreground">From</div>
                    <div>
                        {{
                            message.from_name
                                ? `${message.from_name} <${message.from_address}>`
                                : (message.from_address ?? '—')
                        }}
                    </div>

                    <div class="text-muted-foreground">To</div>
                    <div>{{ formatAddresses(message.to_addresses) }}</div>

                    <template v-if="message.cc_addresses?.length">
                        <div class="text-muted-foreground">CC</div>
                        <div>{{ formatAddresses(message.cc_addresses) }}</div>
                    </template>

                    <template v-if="message.bcc_addresses?.length">
                        <div class="text-muted-foreground">BCC</div>
                        <div>{{ formatAddresses(message.bcc_addresses) }}</div>
                    </template>

                    <div class="text-muted-foreground">Mailer</div>
                    <div>{{ message.mailer ?? '—' }}</div>

                    <div class="text-muted-foreground">Source</div>
                    <div :title="message.source ?? ''">
                        {{
                            message.source_label ?? shortSource(message.source)
                        }}
                    </div>

                    <template v-if="message.message_id">
                        <div class="text-muted-foreground">Message-ID</div>
                        <div class="font-mono text-xs break-all">
                            {{ message.message_id }}
                        </div>
                    </template>

                    <template v-if="message.error">
                        <div class="text-muted-foreground">Error</div>
                        <div class="text-destructive">{{ message.error }}</div>
                    </template>
                </CardContent>
            </Card>

            <Card v-if="message.html_body">
                <CardHeader>
                    <CardTitle>HTML body</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <iframe
                        :srcdoc="message.html_body"
                        sandbox=""
                        class="block min-h-[400px] w-full rounded-b-xl border-0 bg-white"
                        title="Email HTML body (sandboxed)"
                    />
                </CardContent>
            </Card>

            <Card v-if="message.text_body">
                <CardHeader>
                    <CardTitle>Text body</CardTitle>
                </CardHeader>
                <CardContent>
                    <pre
                        class="overflow-x-auto rounded bg-muted/30 p-3 text-xs"
                        >{{ message.text_body }}</pre
                    >
                </CardContent>
            </Card>

            <Card v-if="message.headers">
                <CardHeader>
                    <CardTitle>Headers</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl
                        class="grid gap-x-3 gap-y-1 text-xs sm:grid-cols-[200px_1fr]"
                    >
                        <template
                            v-for="(value, key) in message.headers"
                            :key="key"
                        >
                            <dt class="font-mono text-muted-foreground">
                                {{ key }}
                            </dt>
                            <dd class="font-mono break-all">{{ value }}</dd>
                        </template>
                    </dl>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
