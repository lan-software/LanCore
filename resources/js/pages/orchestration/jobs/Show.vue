<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { RefreshCw, XCircle } from 'lucide-vue-next';
import OrchestrationJobController from '@/actions/App/Domain/Orchestration/Http/Controllers/OrchestrationJobController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as orchestrationJobsRoute } from '@/routes/orchestration-jobs';
import type { BreadcrumbItem } from '@/types';
import type { OrchestrationJob } from '@/types/domain';

const props = defineProps<{
    job: OrchestrationJob;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: orchestrationJobsRoute().url },
    { title: 'Orchestration', href: orchestrationJobsRoute().url },
    { title: 'Jobs', href: orchestrationJobsRoute().url },
    {
        title: `Job #${props.job.id}`,
        href: OrchestrationJobController.show(props.job.id).url,
    },
];

const statusColors: Record<string, string> = {
    pending: 'bg-gray-50 text-gray-700 dark:bg-gray-900 dark:text-gray-400',
    selecting_server:
        'bg-yellow-50 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-400',
    deploying: 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-400',
    active: 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400',
    completed:
        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
    failed: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400',
    cancelled: 'bg-gray-50 text-gray-500 dark:bg-gray-900 dark:text-gray-500',
};

function retryJob() {
    router.post(OrchestrationJobController.retry(props.job.id).url);
}

function cancelJob() {
    router.post(OrchestrationJobController.cancel(props.job.id).url);
}

function formatDate(date: string | null): string {
    if (!date) {
        return '—';
    }

    return new Date(date).toLocaleString();
}
</script>

<template>
    <Head :title="`Orchestration Job #${job.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-4xl flex-1 flex-col gap-6 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="orchestrationJobsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Orchestration
                </Link>
            </div>

            <div class="flex items-center gap-3">
                <Heading
                    :title="`Job #${job.id} — Match ${job.lanbrackets_match_id}`"
                />
                <Badge :class="statusColors[job.status]">
                    {{ job.status.replace(/_/g, ' ') }}
                </Badge>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="job.status === 'failed'"
                        variant="outline"
                        size="sm"
                        @click="retryJob"
                    >
                        <RefreshCw class="mr-1 size-4" />
                        Retry
                    </Button>
                    <Button
                        v-if="
                            job.status === 'pending' || job.status === 'failed'
                        "
                        variant="outline"
                        size="sm"
                        @click="cancelJob"
                    >
                        <XCircle class="mr-1 size-4" />
                        Cancel
                    </Button>
                </div>
            </div>

            <div
                v-if="job.error_message"
                class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950"
            >
                <p class="text-sm font-medium text-red-700 dark:text-red-300">
                    Error
                </p>
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ job.error_message }}
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-4">
                    <Heading variant="small" title="Details" />
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Competition</dt>
                            <dd class="font-medium">
                                {{ job.competition?.name ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Game</dt>
                            <dd class="font-medium">
                                {{ job.game?.name ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Game Mode</dt>
                            <dd class="font-medium">
                                {{ job.game_mode?.name ?? 'Any' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Handler</dt>
                            <dd class="font-mono text-xs">
                                {{
                                    job.match_handler?.split('\\').pop() ?? '—'
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Attempts</dt>
                            <dd class="font-medium">
                                {{ job.attempts }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Timeline" />
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Created</dt>
                            <dd>{{ formatDate(job.created_at) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Started</dt>
                            <dd>{{ formatDate(job.started_at) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Completed</dt>
                            <dd>{{ formatDate(job.completed_at) }}</dd>
                        </div>
                    </dl>

                    <div v-if="job.game_server" class="mt-4">
                        <Heading variant="small" title="Server" />
                        <dl class="mt-2 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Name</dt>
                                <dd class="font-medium">
                                    {{ job.game_server.name }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Address</dt>
                                <dd class="font-mono">
                                    {{ job.game_server.host }}:{{
                                        job.game_server.port
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div v-if="job.match_config" class="space-y-4">
                <Heading variant="small" title="Match Config" />
                <pre
                    class="max-h-64 overflow-auto rounded-lg bg-muted p-4 text-xs"
                    >{{ JSON.stringify(job.match_config, null, 2) }}</pre
                >
            </div>

            <div v-if="job.chat_messages?.length" class="space-y-4">
                <Heading
                    variant="small"
                    title="Match Chat"
                    :description="`${job.chat_messages.length} messages`"
                />
                <div
                    class="max-h-96 overflow-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Time</TableHead>
                                <TableHead>Player</TableHead>
                                <TableHead>Message</TableHead>
                                <TableHead>Type</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="msg in job.chat_messages"
                                :key="msg.id"
                            >
                                <TableCell
                                    class="text-xs whitespace-nowrap text-muted-foreground"
                                >
                                    {{
                                        new Date(
                                            msg.timestamp,
                                        ).toLocaleTimeString()
                                    }}
                                </TableCell>
                                <TableCell class="font-medium">{{
                                    msg.player_name
                                }}</TableCell>
                                <TableCell>{{ msg.message }}</TableCell>
                                <TableCell>
                                    <Badge
                                        v-if="msg.is_team_chat"
                                        variant="outline"
                                        >Team</Badge
                                    >
                                    <Badge v-else variant="secondary"
                                        >All</Badge
                                    >
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
