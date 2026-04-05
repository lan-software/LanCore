<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    Gamepad2,
    Loader2,
    PlugZap,
    Server,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ExternalApiController from '@/actions/App/Domain/Orchestration/Http/Controllers/ExternalApiController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as externalApisRoute } from '@/routes/external-apis';
import type { BreadcrumbItem } from '@/types';

interface ApiConnection {
    enabled: boolean;
    base_url: string;
    has_token: boolean;
    timeout: number;
    retries: number;
}

defineProps<{
    connections: {
        tmt2: ApiConnection;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: externalApisRoute().url },
    { title: 'Orchestration', href: externalApisRoute().url },
    { title: 'External APIs', href: externalApisRoute().url },
];

const page = usePage();

const flash = computed(
    () => (page.props.flash as Record<string, string>) ?? {},
);

const testingTmt2 = ref(false);

function testTmt2() {
    testingTmt2.value = true;
    router.post(
        ExternalApiController.testTmt2().url,
        {},
        {
            preserveScroll: true,
            onFinish: () => (testingTmt2.value = false),
        },
    );
}

function statusBadge(status: string | undefined) {
    if (!status) {
        return null;
    }

    if (status === 'connected') {
        return { label: 'Connected', variant: 'success' as const };
    }

    if (status === 'auth_failed') {
        return { label: 'Auth Failed', variant: 'destructive' as const };
    }

    if (status === 'unreachable') {
        return { label: 'Unreachable', variant: 'destructive' as const };
    }

    return null;
}
</script>

<template>
    <Head title="External APIs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <Heading
                title="External APIs"
                description="Manage connections to external services used for match orchestration. Credentials are configured via environment variables."
            />

            <!-- TMT2 -->
            <div
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950"
                        >
                            <PlugZap
                                class="size-5 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                        <div>
                            <h3 class="text-base font-medium">TMT2</h3>
                            <p class="text-sm text-muted-foreground">
                                Tournament Match Tracker 2 — CS2 match
                                supervision
                            </p>
                        </div>
                    </div>
                    <Badge
                        :class="
                            connections.tmt2.enabled
                                ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400'
                                : 'bg-gray-50 text-gray-500 dark:bg-gray-900 dark:text-gray-500'
                        "
                    >
                        {{ connections.tmt2.enabled ? 'Enabled' : 'Disabled' }}
                    </Badge>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">Base URL</dt>
                        <dd class="font-mono text-xs">
                            {{ connections.tmt2.base_url || '—' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">API Token</dt>
                        <dd>
                            <Badge
                                v-if="connections.tmt2.has_token"
                                variant="outline"
                                class="border-green-300 text-green-700 dark:border-green-700 dark:text-green-400"
                            >
                                <CheckCircle2 class="mr-1 size-3" />
                                Configured
                            </Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="border-red-300 text-red-600 dark:border-red-700 dark:text-red-400"
                            >
                                <XCircle class="mr-1 size-3" />
                                Missing
                            </Badge>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">Timeout</dt>
                        <dd class="text-xs">{{ connections.tmt2.timeout }}s</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">Retries</dt>
                        <dd class="text-xs">{{ connections.tmt2.retries }}</dd>
                    </div>
                </dl>

                <div class="mt-4 flex items-center gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!connections.tmt2.enabled || testingTmt2"
                        @click="testTmt2"
                    >
                        <Loader2
                            v-if="testingTmt2"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        <PlugZap v-else class="mr-1.5 size-4" />
                        Test Connection
                    </Button>
                    <template v-if="flash.tmt2_status">
                        <CheckCircle2
                            v-if="flash.tmt2_status === 'connected'"
                            class="size-4 text-green-600"
                        />
                        <AlertCircle
                            v-else-if="flash.tmt2_status === 'auth_failed'"
                            class="size-4 text-yellow-600"
                        />
                        <XCircle v-else class="size-4 text-red-600" />
                        <span class="text-sm">
                            {{ statusBadge(flash.tmt2_status)?.label }}
                        </span>
                        <span
                            v-if="flash.tmt2_error"
                            class="text-xs text-muted-foreground"
                        >
                            {{ flash.tmt2_error }}
                        </span>
                    </template>
                </div>

                <div
                    class="mt-4 rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground"
                >
                    <p class="font-medium">Environment Variables</p>
                    <code class="mt-1 block">TMT2_ENABLED=true</code>
                    <code class="block">TMT2_BASE_URL=http://tmt2:8080</code>
                    <code class="block">TMT2_TOKEN=your-bearer-token</code>
                    <code class="block">TMT2_TIMEOUT=5</code>
                    <code class="block">TMT2_RETRIES=2</code>
                </div>
            </div>

            <!-- Pelican Panel (placeholder) -->
            <div
                class="rounded-xl border border-sidebar-border/70 p-6 opacity-60 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-950"
                        >
                            <Server
                                class="size-5 text-orange-600 dark:text-orange-400"
                            />
                        </div>
                        <div>
                            <h3 class="text-base font-medium">Pelican Panel</h3>
                            <p class="text-sm text-muted-foreground">
                                Game server provisioning and management
                            </p>
                        </div>
                    </div>
                    <Badge
                        class="bg-gray-50 text-gray-500 dark:bg-gray-900 dark:text-gray-500"
                    >
                        Coming Soon
                    </Badge>
                </div>
                <p class="mt-4 text-sm text-muted-foreground">
                    Pelican Panel integration will enable automated game server
                    provisioning, start/stop controls, and resource monitoring
                    directly from LanCore.
                </p>
            </div>

            <!-- Steam Web API (placeholder) -->
            <div
                class="rounded-xl border border-sidebar-border/70 p-6 opacity-60 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-950"
                        >
                            <Gamepad2
                                class="size-5 text-indigo-600 dark:text-indigo-400"
                            />
                        </div>
                        <div>
                            <h3 class="text-base font-medium">Steam Web API</h3>
                            <p class="text-sm text-muted-foreground">
                                Player profiles, game ownership, and VAC status
                            </p>
                        </div>
                    </div>
                    <Badge
                        class="bg-gray-50 text-gray-500 dark:bg-gray-900 dark:text-gray-500"
                    >
                        Coming Soon
                    </Badge>
                </div>
                <p class="mt-4 text-sm text-muted-foreground">
                    Steam Web API integration will enable player identity
                    verification, game ownership checks, and VAC ban detection
                    for competition eligibility.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
