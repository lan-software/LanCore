<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import confetti from 'canvas-confetti';
import {
    AlertCircle,
    CheckCircle2,
    CreditCard,
    Gamepad2,
    Loader2,
    PlugZap,
    Server,
    Wallet,
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

interface StripeConnection {
    enabled: boolean;
    has_publishable_key: boolean;
    has_secret_key: boolean;
    has_webhook_secret: boolean;
    currency: string;
    currency_locale: string;
}

interface PayPalConnection {
    enabled: boolean;
    mode: string;
    has_client_id: boolean;
    has_client_secret: boolean;
    has_webhook_id: boolean;
}

defineProps<{
    connections: {
        tmt2: ApiConnection;
        stripe: StripeConnection;
        paypal: PayPalConnection;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: externalApisRoute().url },
    { title: 'Orchestration', href: externalApisRoute().url },
    { title: 'External APIs', href: externalApisRoute().url },
];

const testingTmt2 = ref(false);
const testResult = ref<'idle' | 'success' | 'failure'>('idle');
const testError = ref('');
const testStatusLabel = ref('');

function fireConfetti() {
    const end = Date.now() + 2000;

    const frame = () => {
        confetti({
            particleCount: 4,
            angle: 60,
            spread: 55,
            origin: { x: 0, y: 0.7 },
            colors: [
                '#ef4444',
                '#f97316',
                '#eab308',
                '#22c55e',
                '#3b82f6',
                '#8b5cf6',
                '#ec4899',
            ],
        });
        confetti({
            particleCount: 4,
            angle: 120,
            spread: 55,
            origin: { x: 1, y: 0.7 },
            colors: [
                '#ef4444',
                '#f97316',
                '#eab308',
                '#22c55e',
                '#3b82f6',
                '#8b5cf6',
                '#ec4899',
            ],
        });

        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    };

    frame();
}

async function testTmt2() {
    testingTmt2.value = true;
    testResult.value = 'idle';
    testError.value = '';
    testStatusLabel.value = '';

    try {
        const url = ExternalApiController.testTmt2().url;
        const csrfToken =
            document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.content ?? '';

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = await response.json();

        if (data.status === 'connected') {
            testResult.value = 'success';
            testStatusLabel.value = 'Connected!';
            fireConfetti();
        } else {
            testResult.value = 'failure';
            testStatusLabel.value =
                data.status === 'auth_failed' ? 'Auth Failed' : 'Unreachable';
            testError.value = data.error || '';
        }
    } catch {
        testResult.value = 'failure';
        testStatusLabel.value = 'Request Failed';
        testError.value = 'Could not reach the server.';
    } finally {
        testingTmt2.value = false;
    }
}

const testButtonClass = computed(() => {
    if (testResult.value === 'success') {
        return 'border-green-500 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-600 dark:bg-green-950 dark:text-green-400 dark:hover:bg-green-900';
    }

    if (testResult.value === 'failure') {
        return 'border-red-500 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-600 dark:bg-red-950 dark:text-red-400 dark:hover:bg-red-900';
    }

    return '';
});

const testButtonLabel = computed(() => {
    if (testingTmt2.value) {
        return 'Testing...';
    }

    if (testResult.value === 'success') {
        return 'Connected!';
    }

    if (testResult.value === 'failure') {
        return testStatusLabel.value || 'Failed';
    }

    return 'Test Connection';
});

// Stripe test
const testingStripe = ref(false);
const stripeResult = ref<'idle' | 'success' | 'failure'>('idle');
const stripeError = ref('');
const stripeStatusLabel = ref('');
const stripeAccountName = ref('');

async function testStripe() {
    testingStripe.value = true;
    stripeResult.value = 'idle';
    stripeError.value = '';
    stripeStatusLabel.value = '';
    stripeAccountName.value = '';

    try {
        const url = ExternalApiController.testStripe().url;
        const csrfToken =
            document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.content ?? '';

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = await response.json();

        if (data.status === 'connected') {
            stripeResult.value = 'success';
            stripeStatusLabel.value = 'Connected!';
            stripeAccountName.value = data.account ?? '';
            fireConfetti();
        } else {
            stripeResult.value = 'failure';
            stripeStatusLabel.value =
                data.status === 'auth_failed'
                    ? 'Auth Failed'
                    : data.status === 'not_configured'
                      ? 'Not Configured'
                      : 'Unreachable';
            stripeError.value = data.error || '';
        }
    } catch {
        stripeResult.value = 'failure';
        stripeStatusLabel.value = 'Request Failed';
        stripeError.value = 'Could not reach the server.';
    } finally {
        testingStripe.value = false;
    }
}

const stripeButtonClass = computed(() => {
    if (stripeResult.value === 'success') {
        return 'border-green-500 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-600 dark:bg-green-950 dark:text-green-400 dark:hover:bg-green-900';
    }

    if (stripeResult.value === 'failure') {
        return 'border-red-500 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-600 dark:bg-red-950 dark:text-red-400 dark:hover:bg-red-900';
    }

    return '';
});

const stripeButtonLabel = computed(() => {
    if (testingStripe.value) {
        return 'Testing...';
    }

    if (stripeResult.value === 'success') {
        return 'Connected!';
    }

    if (stripeResult.value === 'failure') {
        return stripeStatusLabel.value || 'Failed';
    }

    return 'Test Connection';
});
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
                        :class="testButtonClass"
                        class="min-w-[10rem] transition-colors duration-300"
                        @click="testTmt2"
                    >
                        <Loader2
                            v-if="testingTmt2"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        <CheckCircle2
                            v-else-if="testResult === 'success'"
                            class="mr-1.5 size-4"
                        />
                        <XCircle
                            v-else-if="testResult === 'failure'"
                            class="mr-1.5 size-4"
                        />
                        <PlugZap v-else class="mr-1.5 size-4" />
                        {{ testButtonLabel }}
                    </Button>
                    <template v-if="testResult === 'failure'">
                        <span
                            v-if="testError"
                            class="text-xs text-muted-foreground"
                        >
                            {{ testError }}
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

            <!-- Stripe / Cashier -->
            <div
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-950"
                        >
                            <CreditCard
                                class="size-5 text-violet-600 dark:text-violet-400"
                            />
                        </div>
                        <div>
                            <h3 class="text-base font-medium">Stripe</h3>
                            <p class="text-sm text-muted-foreground">
                                Payment processing via Laravel Cashier
                            </p>
                        </div>
                    </div>
                    <Badge
                        :class="
                            connections.stripe.enabled
                                ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400'
                                : 'bg-gray-50 text-gray-500 dark:bg-gray-900 dark:text-gray-500'
                        "
                    >
                        {{
                            connections.stripe.enabled ? 'Enabled' : 'Disabled'
                        }}
                    </Badge>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">Publishable Key</dt>
                        <dd>
                            <Badge
                                v-if="connections.stripe.has_publishable_key"
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
                        <dt class="text-muted-foreground">Secret Key</dt>
                        <dd>
                            <Badge
                                v-if="connections.stripe.has_secret_key"
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
                        <dt class="text-muted-foreground">Webhook Secret</dt>
                        <dd>
                            <Badge
                                v-if="connections.stripe.has_webhook_secret"
                                variant="outline"
                                class="border-green-300 text-green-700 dark:border-green-700 dark:text-green-400"
                            >
                                <CheckCircle2 class="mr-1 size-3" />
                                Configured
                            </Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="border-yellow-300 text-yellow-700 dark:border-yellow-700 dark:text-yellow-400"
                            >
                                <AlertCircle class="mr-1 size-3" />
                                Not set
                            </Badge>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">Currency</dt>
                        <dd class="text-xs">
                            {{ connections.stripe.currency }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-4 flex items-center gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!connections.stripe.enabled || testingStripe"
                        :class="stripeButtonClass"
                        class="min-w-[10rem] transition-colors duration-300"
                        @click="testStripe"
                    >
                        <Loader2
                            v-if="testingStripe"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        <CheckCircle2
                            v-else-if="stripeResult === 'success'"
                            class="mr-1.5 size-4"
                        />
                        <XCircle
                            v-else-if="stripeResult === 'failure'"
                            class="mr-1.5 size-4"
                        />
                        <CreditCard v-else class="mr-1.5 size-4" />
                        {{ stripeButtonLabel }}
                    </Button>
                    <span
                        v-if="stripeResult === 'success' && stripeAccountName"
                        class="text-xs text-muted-foreground"
                    >
                        {{ stripeAccountName }}
                    </span>
                    <template v-if="stripeResult === 'failure'">
                        <span
                            v-if="stripeError"
                            class="text-xs text-muted-foreground"
                        >
                            {{ stripeError }}
                        </span>
                    </template>
                </div>

                <div
                    class="mt-4 rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground"
                >
                    <p class="font-medium">Environment Variables</p>
                    <code class="mt-1 block">STRIPE_KEY=pk_test_...</code>
                    <code class="block">STRIPE_SECRET=sk_test_...</code>
                    <code class="block">STRIPE_WEBHOOK_SECRET=whsec_...</code>
                    <code class="block">CASHIER_CURRENCY=eur</code>
                </div>
            </div>

            <!-- PayPal -->
            <div
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-950"
                        >
                            <Wallet
                                class="size-5 text-sky-600 dark:text-sky-400"
                            />
                        </div>
                        <div>
                            <h3 class="text-base font-medium">PayPal</h3>
                            <p class="text-sm text-muted-foreground">
                                Payment processing via PayPal Orders v2 (srmklive/paypal)
                            </p>
                        </div>
                    </div>
                    <Badge
                        :class="
                            connections.paypal.enabled
                                ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400'
                                : 'bg-gray-50 text-gray-500 dark:bg-gray-900 dark:text-gray-500'
                        "
                    >
                        {{
                            connections.paypal.enabled ? 'Enabled' : 'Disabled'
                        }}
                    </Badge>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">Mode</dt>
                        <dd class="text-xs font-mono uppercase">
                            {{ connections.paypal.mode }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">Client ID</dt>
                        <dd>
                            <Badge
                                v-if="connections.paypal.has_client_id"
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
                        <dt class="text-muted-foreground">Client Secret</dt>
                        <dd>
                            <Badge
                                v-if="connections.paypal.has_client_secret"
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
                        <dt class="text-muted-foreground">Webhook ID</dt>
                        <dd>
                            <Badge
                                v-if="connections.paypal.has_webhook_id"
                                variant="outline"
                                class="border-green-300 text-green-700 dark:border-green-700 dark:text-green-400"
                            >
                                <CheckCircle2 class="mr-1 size-3" />
                                Configured
                            </Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="border-yellow-300 text-yellow-700 dark:border-yellow-700 dark:text-yellow-400"
                            >
                                <AlertCircle class="mr-1 size-3" />
                                Not set
                            </Badge>
                        </dd>
                    </div>
                </dl>

                <div
                    class="mt-4 rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground"
                >
                    <p class="font-medium">Environment Variables</p>
                    <code class="mt-1 block">PAYPAL_MODE=sandbox</code>
                    <code class="block">PAYPAL_SANDBOX_CLIENT_ID=...</code>
                    <code class="block">PAYPAL_SANDBOX_CLIENT_SECRET=...</code>
                    <code class="block">PAYPAL_WEBHOOK_ID=...</code>
                    <p class="mt-2 text-[11px]">
                        Populate PAYPAL_WEBHOOK_ID by running
                        <code>php artisan paypal:webhook:register</code>.
                    </p>
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
