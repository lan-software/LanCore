<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    CreditCard,
    Banknote,
    Coins,
    FileText,
    Loader2,
    Wallet,
} from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as shopSettingsRoute } from '@/routes/shop-settings';
import type { BreadcrumbItem } from '@/types';

interface PaymentMethodInfo {
    value: string;
    label: string;
    requires_redirect: boolean;
    enabled: boolean;
}

interface InvoiceConfig {
    invoice_prefix: string;
    invoice_footer: string;
    invoice_notes: string;
}

interface CurrencyOption {
    value: string;
    label: string;
    symbol: string;
}

const props = defineProps<{
    paymentMethods: PaymentMethodInfo[];
    invoiceConfig: InvoiceConfig;
    currency: string;
    availableCurrencies: CurrencyOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: shopSettingsRoute().url },
    { title: 'Shop', href: shopSettingsRoute().url },
    { title: 'Settings', href: shopSettingsRoute().url },
];

const methods = reactive<Record<string, boolean>>(
    Object.fromEntries(props.paymentMethods.map((m) => [m.value, m.enabled])),
);

const saving = ref(false);
const saved = ref(false);

function save() {
    saving.value = true;
    saved.value = false;
    router.patch(
        '/shop-settings/payment-methods',
        { methods },
        {
            preserveScroll: true,
            onSuccess: () => {
                saved.value = true;
                setTimeout(() => (saved.value = false), 2000);
            },
            onFinish: () => (saving.value = false),
        },
    );
}

function methodIcon(value: string) {
    if (value === 'stripe') {
        return CreditCard;
    }

    if (value === 'paypal') {
        return Wallet;
    }

    return Banknote;
}

function methodDescription(value: string): string {
    const map: Record<string, string> = {
        stripe: 'Process payments via Stripe Checkout. Requires STRIPE_KEY and STRIPE_SECRET environment variables.',
        on_site:
            'Allow attendees to pay at the venue during check-in. Orders are marked as pending until staff confirms payment.',
        paypal: 'Process payments via PayPal Checkout. Requires PAYPAL_MODE and PayPal client credentials; webhook id populated via `php artisan paypal:webhook:register`.',
    };

    return map[value] ?? '';
}

function methodAccent(value: string): {
    wrapper: string;
    icon: string;
} {
    if (value === 'stripe') {
        return {
            wrapper: 'bg-violet-50 dark:bg-violet-950',
            icon: 'text-violet-600 dark:text-violet-400',
        };
    }

    if (value === 'paypal') {
        return {
            wrapper: 'bg-sky-50 dark:bg-sky-950',
            icon: 'text-sky-600 dark:text-sky-400',
        };
    }

    return {
        wrapper: 'bg-emerald-50 dark:bg-emerald-950',
        icon: 'text-emerald-600 dark:text-emerald-400',
    };
}

const currencyForm = reactive({ currency: props.currency });
const savingCurrency = ref(false);
const savedCurrency = ref(false);

function saveCurrency() {
    savingCurrency.value = true;
    savedCurrency.value = false;
    router.patch(
        '/shop-settings/currency',
        { currency: currencyForm.currency },
        {
            preserveScroll: true,
            onSuccess: () => {
                savedCurrency.value = true;
                setTimeout(() => (savedCurrency.value = false), 2000);
            },
            onFinish: () => (savingCurrency.value = false),
        },
    );
}

// Invoice config
const invoiceForm = reactive({
    invoice_prefix: props.invoiceConfig.invoice_prefix,
    invoice_footer: props.invoiceConfig.invoice_footer,
    invoice_notes: props.invoiceConfig.invoice_notes,
});

const savingInvoice = ref(false);
const savedInvoice = ref(false);

function saveInvoice() {
    savingInvoice.value = true;
    savedInvoice.value = false;
    router.patch('/shop-settings/invoice', invoiceForm, {
        preserveScroll: true,
        onSuccess: () => {
            savedInvoice.value = true;
            setTimeout(() => (savedInvoice.value = false), 2000);
        },
        onFinish: () => (savingInvoice.value = false),
    });
}
</script>

<template>
    <Head title="Shop Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <Heading
                title="Shop Settings"
                description="Configure payment providers and shop behavior."
            />

            <!-- Currency -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Coins class="size-4 text-muted-foreground" />
                    Currency
                </div>

                <div
                    class="rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
                >
                    <div class="grid gap-2">
                        <Label for="currency">Shop Currency</Label>
                        <select
                            id="currency"
                            v-model="currencyForm.currency"
                            class="block w-full max-w-xs rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:border-ring focus:ring-1 focus:ring-ring focus:outline-none"
                        >
                            <option
                                v-for="option in availableCurrencies"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }} ({{ option.symbol }})
                            </option>
                        </select>
                        <p class="text-xs text-muted-foreground">
                            All new orders snapshot this currency on checkout.
                            Historical invoices and receipts keep the currency
                            they were placed in.
                        </p>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <Button
                            :disabled="savingCurrency"
                            @click="saveCurrency"
                        >
                            <Loader2
                                v-if="savingCurrency"
                                class="mr-1.5 size-4 animate-spin"
                            />
                            {{ savingCurrency ? 'Saving...' : 'Save Currency' }}
                        </Button>
                        <span
                            v-if="savedCurrency"
                            class="flex items-center gap-1 text-sm text-green-600"
                        >
                            <CheckCircle2 class="size-4" /> Saved
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment Providers -->
            <div class="space-y-4">
                <h2 class="text-sm font-semibold">Payment Providers</h2>

                <div
                    v-for="method in paymentMethods"
                    :key="method.value"
                    class="rounded-xl border border-sidebar-border/70 p-5 transition dark:border-sidebar-border"
                    :class="methods[method.value] ? '' : 'opacity-60'"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-lg"
                                :class="methodAccent(method.value).wrapper"
                            >
                                <component
                                    :is="methodIcon(method.value)"
                                    class="size-5"
                                    :class="methodAccent(method.value).icon"
                                />
                            </div>
                            <div>
                                <h3 class="text-base font-medium">
                                    {{ method.label }}
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    {{ methodDescription(method.value) }}
                                </p>
                            </div>
                        </div>

                        <label
                            class="relative inline-flex cursor-pointer items-center"
                        >
                            <input
                                type="checkbox"
                                v-model="methods[method.value]"
                                class="peer sr-only"
                            />
                            <div
                                class="peer h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-ring peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-gray-700"
                            ></div>
                        </label>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <Badge variant="outline" class="text-[10px]">
                            {{
                                method.requires_redirect
                                    ? 'External checkout'
                                    : 'Inline payment'
                            }}
                        </Badge>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Button :disabled="saving" @click="save">
                    <Loader2 v-if="saving" class="mr-1.5 size-4 animate-spin" />
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </Button>
                <span
                    v-if="saved"
                    class="flex items-center gap-1 text-sm text-green-600"
                >
                    <CheckCircle2 class="size-4" /> Saved
                </span>
            </div>
            <!-- Invoice Configuration -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <FileText class="size-4 text-muted-foreground" />
                    Invoice & Receipt Configuration
                </div>

                <div
                    class="rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
                >
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="invoice_prefix"
                                >Invoice Number Prefix</Label
                            >
                            <Input
                                id="invoice_prefix"
                                v-model="invoiceForm.invoice_prefix"
                                placeholder="INV-"
                                class="max-w-xs"
                            />
                            <p class="text-xs text-muted-foreground">
                                Prefix for generated invoice numbers, e.g.
                                INV-2026-00001
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="invoice_notes">Invoice Notes</Label>
                            <Textarea
                                id="invoice_notes"
                                v-model="invoiceForm.invoice_notes"
                                rows="3"
                                placeholder="Payment terms, return policy..."
                            />
                            <p class="text-xs text-muted-foreground">
                                Shown in the notes section of invoices
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="invoice_footer">Invoice Footer</Label>
                            <Textarea
                                id="invoice_footer"
                                v-model="invoiceForm.invoice_footer"
                                rows="2"
                                placeholder="Custom footer text..."
                            />
                            <p class="text-xs text-muted-foreground">
                                Additional text printed at the bottom of
                                invoices and receipts
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <Button :disabled="savingInvoice" @click="saveInvoice">
                            <Loader2
                                v-if="savingInvoice"
                                class="mr-1.5 size-4 animate-spin"
                            />
                            {{
                                savingInvoice
                                    ? 'Saving...'
                                    : 'Save Invoice Settings'
                            }}
                        </Button>
                        <span
                            v-if="savedInvoice"
                            class="flex items-center gap-1 text-sm text-green-600"
                        >
                            <CheckCircle2 class="size-4" /> Saved
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
