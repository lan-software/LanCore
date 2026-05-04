<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import confetti from 'canvas-confetti';
import {
    AlertCircle,
    CheckCircle2,
    CreditCard,
    Gamepad2,
    Loader2,
    Mail,
    PlugZap,
    RefreshCw,
    Server,
    Wallet,
    XCircle,
} from 'lucide-vue-next';
import NewsletterListSyncController from '@/actions/App/Domain/Newsletter/Http/Controllers/Admin/NewsletterListSyncController';
import ExternalApiController from '@/actions/App/Domain/Orchestration/Http/Controllers/ExternalApiController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useExternalApiTest } from '@/composables/useExternalApiTest';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as externalApisRoute } from '@/routes/external-apis';
import { index as newsletterListsRoute } from '@/routes/newsletter-lists';
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

interface SteamConnection {
    enabled: boolean;
    has_api_key: boolean;
    has_redirect_uri: boolean;
}

interface ListmonkConnection {
    enabled: boolean;
    base_url: string;
    has_username: boolean;
    has_password: boolean;
    preconfirm_subscriptions: boolean;
}

defineProps<{
    connections: {
        tmt2: ApiConnection;
        stripe: StripeConnection;
        paypal: PayPalConnection;
        steam: SteamConnection;
        listmonk: ListmonkConnection;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: externalApisRoute().url },
    { title: 'Orchestration', href: externalApisRoute().url },
    { title: 'External APIs', href: externalApisRoute().url },
];

function fireConfetti() {
    const end = Date.now() + 2000;
    const palette = [
        '#ef4444',
        '#f97316',
        '#eab308',
        '#22c55e',
        '#3b82f6',
        '#8b5cf6',
        '#ec4899',
    ];

    const frame = () => {
        confetti({
            particleCount: 4,
            angle: 60,
            spread: 55,
            origin: { x: 0, y: 0.7 },
            colors: palette,
        });
        confetti({
            particleCount: 4,
            angle: 120,
            spread: 55,
            origin: { x: 1, y: 0.7 },
            colors: palette,
        });

        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    };

    frame();
}

const tmt2Test = useExternalApiTest(
    () => ExternalApiController.testTmt2().url,
    fireConfetti,
);
const stripeTest = useExternalApiTest(
    () => ExternalApiController.testStripe().url,
    fireConfetti,
);
const paypalTest = useExternalApiTest(
    () => ExternalApiController.testPaypal().url,
    fireConfetti,
);
const steamTest = useExternalApiTest(
    () => ExternalApiController.testSteam().url,
    fireConfetti,
);
const listmonkTest = useExternalApiTest(
    () => ExternalApiController.testListmonk().url,
    fireConfetti,
);

function fetchListmonkLists() {
    router.post(NewsletterListSyncController.fetch().url);
}
</script>

<template>
    <Head title="External APIs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <Heading
                title="External APIs"
                description="Manage connections to external services. Credentials are configured via environment variables. Each card exposes a connectivity test that mirrors the `external-apis:test:*` console commands."
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
                        :disabled="
                            !connections.tmt2.enabled || tmt2Test.testing.value
                        "
                        :class="tmt2Test.buttonClass.value"
                        class="min-w-[10rem] transition-colors duration-300"
                        @click="tmt2Test.run()"
                    >
                        <Loader2
                            v-if="tmt2Test.testing.value"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        <CheckCircle2
                            v-else-if="tmt2Test.result.value === 'success'"
                            class="mr-1.5 size-4"
                        />
                        <XCircle
                            v-else-if="tmt2Test.result.value === 'failure'"
                            class="mr-1.5 size-4"
                        />
                        <PlugZap v-else class="mr-1.5 size-4" />
                        {{ tmt2Test.buttonLabel() }}
                    </Button>
                    <span
                        v-if="
                            tmt2Test.result.value === 'failure' &&
                            tmt2Test.error.value
                        "
                        class="text-xs text-muted-foreground"
                    >
                        {{ tmt2Test.error.value }}
                    </span>
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
                        :disabled="
                            !connections.stripe.enabled ||
                            stripeTest.testing.value
                        "
                        :class="stripeTest.buttonClass.value"
                        class="min-w-[10rem] transition-colors duration-300"
                        @click="stripeTest.run()"
                    >
                        <Loader2
                            v-if="stripeTest.testing.value"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        <CheckCircle2
                            v-else-if="stripeTest.result.value === 'success'"
                            class="mr-1.5 size-4"
                        />
                        <XCircle
                            v-else-if="stripeTest.result.value === 'failure'"
                            class="mr-1.5 size-4"
                        />
                        <CreditCard v-else class="mr-1.5 size-4" />
                        {{ stripeTest.buttonLabel() }}
                    </Button>
                    <span
                        v-if="
                            stripeTest.result.value === 'success' &&
                            stripeTest.accountName.value
                        "
                        class="text-xs text-muted-foreground"
                    >
                        {{ stripeTest.accountName.value }}
                    </span>
                    <span
                        v-if="
                            stripeTest.result.value === 'failure' &&
                            stripeTest.error.value
                        "
                        class="text-xs text-muted-foreground"
                    >
                        {{ stripeTest.error.value }}
                    </span>
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
                                Payment processing via PayPal Orders v2
                                (srmklive/paypal)
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
                        <dd class="font-mono text-xs uppercase">
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

                <div class="mt-4 flex items-center gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="
                            !connections.paypal.enabled ||
                            paypalTest.testing.value
                        "
                        :class="paypalTest.buttonClass.value"
                        class="min-w-[10rem] transition-colors duration-300"
                        @click="paypalTest.run()"
                    >
                        <Loader2
                            v-if="paypalTest.testing.value"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        <CheckCircle2
                            v-else-if="paypalTest.result.value === 'success'"
                            class="mr-1.5 size-4"
                        />
                        <XCircle
                            v-else-if="paypalTest.result.value === 'failure'"
                            class="mr-1.5 size-4"
                        />
                        <Wallet v-else class="mr-1.5 size-4" />
                        {{ paypalTest.buttonLabel() }}
                    </Button>
                    <span
                        v-if="
                            paypalTest.result.value === 'failure' &&
                            paypalTest.error.value
                        "
                        class="text-xs text-muted-foreground"
                    >
                        {{ paypalTest.error.value }}
                    </span>
                </div>

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

            <!-- Steam Web API -->
            <div
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
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
                                OpenID login + profile lookups via the Steam Web
                                API
                            </p>
                        </div>
                    </div>
                    <Badge
                        :class="
                            connections.steam.enabled
                                ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400'
                                : 'bg-gray-50 text-gray-500 dark:bg-gray-900 dark:text-gray-500'
                        "
                    >
                        {{ connections.steam.enabled ? 'Enabled' : 'Disabled' }}
                    </Badge>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">API Key</dt>
                        <dd>
                            <Badge
                                v-if="connections.steam.has_api_key"
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
                        <dt class="text-muted-foreground">Redirect URI</dt>
                        <dd>
                            <Badge
                                v-if="connections.steam.has_redirect_uri"
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

                <div class="mt-4 flex items-center gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="
                            !connections.steam.enabled ||
                            steamTest.testing.value
                        "
                        :class="steamTest.buttonClass.value"
                        class="min-w-[10rem] transition-colors duration-300"
                        @click="steamTest.run()"
                    >
                        <Loader2
                            v-if="steamTest.testing.value"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        <CheckCircle2
                            v-else-if="steamTest.result.value === 'success'"
                            class="mr-1.5 size-4"
                        />
                        <XCircle
                            v-else-if="steamTest.result.value === 'failure'"
                            class="mr-1.5 size-4"
                        />
                        <Gamepad2 v-else class="mr-1.5 size-4" />
                        {{ steamTest.buttonLabel() }}
                    </Button>
                    <span
                        v-if="
                            steamTest.result.value === 'success' &&
                            steamTest.accountName.value
                        "
                        class="text-xs text-muted-foreground"
                    >
                        Resolved {{ steamTest.accountName.value }}
                    </span>
                    <span
                        v-if="
                            steamTest.result.value === 'failure' &&
                            steamTest.error.value
                        "
                        class="text-xs text-muted-foreground"
                    >
                        {{ steamTest.error.value }}
                    </span>
                </div>

                <div
                    class="mt-4 rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground"
                >
                    <p class="font-medium">Environment Variables</p>
                    <code class="mt-1 block">STEAM_API_KEY=...</code>
                    <code class="block">STEAM_REDIRECT_URI=...</code>
                    <code class="block"
                        >STEAM_ALLOWED_HOSTS=lan.example,...</code
                    >
                </div>
            </div>

            <!-- Listmonk -->
            <div
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950"
                        >
                            <Mail
                                class="size-5 text-amber-600 dark:text-amber-400"
                            />
                        </div>
                        <div>
                            <h3 class="text-base font-medium">Listmonk</h3>
                            <p class="text-sm text-muted-foreground">
                                Self-hosted newsletter platform — LanCore acts
                                as the subscriber ingress
                            </p>
                        </div>
                    </div>
                    <Badge
                        :class="
                            connections.listmonk.enabled
                                ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400'
                                : 'bg-gray-50 text-gray-500 dark:bg-gray-900 dark:text-gray-500'
                        "
                    >
                        {{
                            connections.listmonk.enabled
                                ? 'Enabled'
                                : 'Disabled'
                        }}
                    </Badge>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">Base URL</dt>
                        <dd class="font-mono text-xs">
                            {{ connections.listmonk.base_url || '—' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">Username</dt>
                        <dd>
                            <Badge
                                v-if="connections.listmonk.has_username"
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
                        <dt class="text-muted-foreground">Password</dt>
                        <dd>
                            <Badge
                                v-if="connections.listmonk.has_password"
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
                        <dt class="text-muted-foreground">
                            Single opt-in (preconfirm)
                        </dt>
                        <dd class="text-xs">
                            {{
                                connections.listmonk.preconfirm_subscriptions
                                    ? 'On'
                                    : 'Off (double opt-in)'
                            }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="
                            !connections.listmonk.enabled ||
                            listmonkTest.testing.value
                        "
                        :class="listmonkTest.buttonClass.value"
                        class="min-w-[10rem] transition-colors duration-300"
                        @click="listmonkTest.run()"
                    >
                        <Loader2
                            v-if="listmonkTest.testing.value"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        <CheckCircle2
                            v-else-if="listmonkTest.result.value === 'success'"
                            class="mr-1.5 size-4"
                        />
                        <XCircle
                            v-else-if="listmonkTest.result.value === 'failure'"
                            class="mr-1.5 size-4"
                        />
                        <Mail v-else class="mr-1.5 size-4" />
                        {{ listmonkTest.buttonLabel() }}
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!connections.listmonk.enabled"
                        @click="fetchListmonkLists"
                    >
                        <RefreshCw class="mr-1.5 size-4" />
                        Fetch lists
                    </Button>
                    <a
                        :href="newsletterListsRoute().url"
                        class="text-xs text-muted-foreground underline"
                    >
                        Newsletter Lists admin →
                    </a>
                    <span
                        v-if="
                            listmonkTest.result.value === 'success' &&
                            listmonkTest.accountName.value
                        "
                        class="text-xs text-muted-foreground"
                    >
                        Listmonk {{ listmonkTest.accountName.value }}
                    </span>
                    <span
                        v-if="
                            listmonkTest.result.value === 'failure' &&
                            listmonkTest.error.value
                        "
                        class="text-xs text-muted-foreground"
                    >
                        {{ listmonkTest.error.value }}
                    </span>
                </div>

                <div
                    class="mt-4 rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground"
                >
                    <p class="font-medium">Environment Variables</p>
                    <code class="mt-1 block">LISTMONK_ENABLED=true</code>
                    <code class="block"
                        >LISTMONK_BASE_URL=https://listmonk.example</code
                    >
                    <code class="block">LISTMONK_USERNAME=admin</code>
                    <code class="block">LISTMONK_PASSWORD=...</code>
                    <code class="block">LISTMONK_PRECONFIRM=false</code>
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
        </div>
    </AppLayout>
</template>
