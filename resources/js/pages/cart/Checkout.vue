<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, FileText, ShieldCheck } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import CartController from '@/actions/App/Domain/Shop/Http/Controllers/CartController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { show as cartShow } from '@/routes/cart';
import { acknowledge as cartAcknowledge } from '@/routes/cart';
import type { Event } from '@/types/domain';

type CartItemSummary = {
    name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    is_addon: boolean;
};

type ConditionItem = {
    id: number;
    name: string;
    description: string | null;
    content: string | null;
    acknowledgement_label: string;
    is_required: boolean;
    requires_scroll: boolean;
};

type PurchaseRequirementItem = {
    id: number;
    name: string;
    description: string | null;
    requirements_content: string | null;
    acknowledgements: string[] | null;
    requires_scroll: boolean;
};

const props = defineProps<{
    cartItems: CartItemSummary[];
    event: Event | null;
    subtotal: number;
    discount: number;
    total: number;
    voucher: { code: string; discount: number } | null;
    paymentMethod: string;
    paymentMethodLabel: string;
    purchaseRequirements: PurchaseRequirementItem[];
    globalConditions: ConditionItem[];
    providerConditions: ConditionItem[];
}>();

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

// Track acknowledgement state using reactive for proper Vue tracking
const globalChecks = reactive<Record<number, boolean>>({});
const providerChecks = reactive<Record<number, boolean>>({});
const requirementChecks = reactive<Record<string, boolean>>({});
const isSubmitting = ref(false);

// Track which scroll containers have been scrolled to the bottom
const scrolledToBottom = reactive<Record<string, boolean>>({});

// Initialize all check states to false on mount
onMounted(() => {
    for (const cond of props.globalConditions) {
        globalChecks[cond.id] = false;

        if (cond.requires_scroll && cond.content) {
            scrolledToBottom[`global-${cond.id}`] = false;
        }
    }

    for (const cond of props.providerConditions) {
        providerChecks[cond.id] = false;

        if (cond.requires_scroll && cond.content) {
            scrolledToBottom[`provider-${cond.id}`] = false;
        }
    }

    for (const req of props.purchaseRequirements) {
        if (req.acknowledgements) {
            for (let i = 0; i < req.acknowledgements.length; i++) {
                requirementChecks[`${req.id}-${i}`] = false;
            }
        }

        if (req.requires_scroll && req.requirements_content) {
            scrolledToBottom[`req-${req.id}`] = false;
        }
    }
});

function handleScroll(event: globalThis.Event, key: string) {
    const el = event.target as HTMLElement;
    const isAtBottom =
        Math.abs(el.scrollHeight - el.scrollTop - el.clientHeight) < 5;

    if (isAtBottom) {
        scrolledToBottom[key] = true;
    }
}

function isCheckboxDisabled(
    scrollKey: string,
    requiresScroll: boolean,
    hasContent: boolean,
): boolean {
    if (!requiresScroll || !hasContent) {
        return false;
    }

    return !scrolledToBottom[scrollKey];
}

// Save acknowledgement to back-end immediately
function saveAcknowledgement(
    type: string,
    id: number,
    key: string | null = null,
) {
    axios.post(cartAcknowledge().url, {
        acknowledgeable_type: type,
        acknowledgeable_id: id,
        acknowledgement_key: key,
    });
}

function onGlobalCheck(condId: number, val: boolean | 'indeterminate') {
    globalChecks[condId] = val === true;

    if (val === true) {
        saveAcknowledgement('global_purchase_condition', condId);
    }
}

function onProviderCheck(condId: number, val: boolean | 'indeterminate') {
    providerChecks[condId] = val === true;

    if (val === true) {
        saveAcknowledgement('payment_provider_condition', condId);
    }
}

function onRequirementCheck(
    reqId: number,
    idx: number,
    val: boolean | 'indeterminate',
) {
    requirementChecks[`${reqId}-${idx}`] = val === true;

    if (val === true) {
        saveAcknowledgement('purchase_requirement', reqId, String(idx));
    }
}

// Build list of all required acknowledgements
const allRequiredSatisfied = computed(() => {
    for (const cond of props.globalConditions) {
        if (cond.is_required && !globalChecks[cond.id]) {
            return false;
        }
    }

    for (const cond of props.providerConditions) {
        if (cond.is_required && !providerChecks[cond.id]) {
            return false;
        }
    }

    for (const req of props.purchaseRequirements) {
        if (req.acknowledgements) {
            for (let i = 0; i < req.acknowledgements.length; i++) {
                if (!requirementChecks[`${req.id}-${i}`]) {
                    return false;
                }
            }
        }
    }

    return true;
});

const hasAnyConditions = computed(
    () =>
        props.globalConditions.length > 0 ||
        props.providerConditions.length > 0 ||
        props.purchaseRequirements.length > 0,
);

function submitCheckout() {
    isSubmitting.value = true;
    router.post(
        CartController.checkout().url,
        { payment_method: props.paymentMethod },
        {
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Checkout Review" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4"
            >
                <Link href="/" class="text-lg font-semibold">LanCore</Link>
                <nav class="flex items-center gap-4">
                    <Link
                        :href="cartShow().url"
                        class="text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="inline size-4" /> Back to Cart
                    </Link>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <div class="mx-auto max-w-4xl px-6 py-12">
                <div class="mb-8 flex items-center gap-3">
                    <ShieldCheck class="size-8" />
                    <h1 class="text-3xl font-bold tracking-tight">
                        Review & Confirm
                    </h1>
                </div>

                <div class="grid gap-8 lg:grid-cols-3">
                    <!-- Left: Conditions & Requirements -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Purchase Requirements -->
                        <Card
                            v-for="req in purchaseRequirements"
                            :key="`req-${req.id}`"
                        >
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="size-5" />
                                    {{ req.name }}
                                </CardTitle>
                                <p
                                    v-if="req.description"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ req.description }}
                                </p>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div
                                    v-if="req.requirements_content"
                                    class="prose prose-sm dark:prose-invert max-h-48 overflow-y-auto rounded-md border bg-muted/50 p-4 text-sm"
                                    v-html="req.requirements_content"
                                    @scroll="
                                        (e: globalThis.Event) =>
                                            handleScroll(e, `req-${req.id}`)
                                    "
                                />

                                <div
                                    v-if="req.acknowledgements"
                                    class="space-y-3"
                                >
                                    <TooltipProvider
                                        v-for="(
                                            ack, idx
                                        ) in req.acknowledgements"
                                        :key="`${req.id}-${idx}`"
                                    >
                                        <div class="flex items-start gap-2">
                                            <Tooltip
                                                :disabled="
                                                    !isCheckboxDisabled(
                                                        `req-${req.id}`,
                                                        req.requires_scroll,
                                                        !!req.requirements_content,
                                                    )
                                                "
                                            >
                                                <TooltipTrigger as-child>
                                                    <span>
                                                        <Checkbox
                                                            :id="`req-${req.id}-${idx}`"
                                                            :model-value="
                                                                requirementChecks[
                                                                    `${req.id}-${idx}`
                                                                ] ?? false
                                                            "
                                                            :disabled="
                                                                isCheckboxDisabled(
                                                                    `req-${req.id}`,
                                                                    req.requires_scroll,
                                                                    !!req.requirements_content,
                                                                )
                                                            "
                                                            @update:model-value="
                                                                (
                                                                    val:
                                                                        | boolean
                                                                        | 'indeterminate',
                                                                ) =>
                                                                    onRequirementCheck(
                                                                        req.id,
                                                                        idx,
                                                                        val,
                                                                    )
                                                            "
                                                        />
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent side="right">
                                                    <p>
                                                        Erst lesen dann klicken
                                                    </p>
                                                </TooltipContent>
                                            </Tooltip>
                                            <Label
                                                :for="`req-${req.id}-${idx}`"
                                                class="text-sm leading-relaxed"
                                                :class="
                                                    isCheckboxDisabled(
                                                        `req-${req.id}`,
                                                        req.requires_scroll,
                                                        !!req.requirements_content,
                                                    )
                                                        ? 'cursor-not-allowed text-muted-foreground'
                                                        : 'cursor-pointer'
                                                "
                                            >
                                                {{ ack }}
                                            </Label>
                                        </div>
                                    </TooltipProvider>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Global Purchase Conditions -->
                        <Card v-if="globalConditions.length > 0">
                            <CardHeader>
                                <CardTitle>General Conditions</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div
                                    v-for="cond in globalConditions"
                                    :key="`global-${cond.id}`"
                                    class="space-y-3"
                                >
                                    <div
                                        v-if="cond.content"
                                        class="prose prose-sm dark:prose-invert max-h-48 overflow-y-auto rounded-md border bg-muted/50 p-4 text-sm"
                                        v-html="cond.content"
                                        @scroll="
                                            (e: globalThis.Event) =>
                                                handleScroll(
                                                    e,
                                                    `global-${cond.id}`,
                                                )
                                        "
                                    />
                                    <TooltipProvider>
                                        <div class="flex items-start gap-2">
                                            <Tooltip
                                                :disabled="
                                                    !isCheckboxDisabled(
                                                        `global-${cond.id}`,
                                                        cond.requires_scroll,
                                                        !!cond.content,
                                                    )
                                                "
                                            >
                                                <TooltipTrigger as-child>
                                                    <span>
                                                        <Checkbox
                                                            :id="`global-${cond.id}`"
                                                            :model-value="
                                                                globalChecks[
                                                                    cond.id
                                                                ] ?? false
                                                            "
                                                            :disabled="
                                                                isCheckboxDisabled(
                                                                    `global-${cond.id}`,
                                                                    cond.requires_scroll,
                                                                    !!cond.content,
                                                                )
                                                            "
                                                            @update:model-value="
                                                                (
                                                                    val:
                                                                        | boolean
                                                                        | 'indeterminate',
                                                                ) =>
                                                                    onGlobalCheck(
                                                                        cond.id,
                                                                        val,
                                                                    )
                                                            "
                                                        />
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent side="right">
                                                    <p>
                                                        Erst lesen dann klicken
                                                    </p>
                                                </TooltipContent>
                                            </Tooltip>
                                            <Label
                                                :for="`global-${cond.id}`"
                                                class="text-sm leading-relaxed"
                                                :class="
                                                    isCheckboxDisabled(
                                                        `global-${cond.id}`,
                                                        cond.requires_scroll,
                                                        !!cond.content,
                                                    )
                                                        ? 'cursor-not-allowed text-muted-foreground'
                                                        : 'cursor-pointer'
                                                "
                                            >
                                                {{ cond.acknowledgement_label }}
                                                <span
                                                    v-if="cond.is_required"
                                                    class="text-destructive"
                                                    >*</span
                                                >
                                            </Label>
                                        </div>
                                    </TooltipProvider>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Payment Provider Conditions -->
                        <Card v-if="providerConditions.length > 0">
                            <CardHeader>
                                <CardTitle
                                    >{{
                                        paymentMethodLabel
                                    }}
                                    Conditions</CardTitle
                                >
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div
                                    v-for="cond in providerConditions"
                                    :key="`provider-${cond.id}`"
                                    class="space-y-3"
                                >
                                    <div
                                        v-if="cond.content"
                                        class="prose prose-sm dark:prose-invert max-h-48 overflow-y-auto rounded-md border bg-muted/50 p-4 text-sm"
                                        v-html="cond.content"
                                        @scroll="
                                            (e: globalThis.Event) =>
                                                handleScroll(
                                                    e,
                                                    `provider-${cond.id}`,
                                                )
                                        "
                                    />
                                    <TooltipProvider>
                                        <div class="flex items-start gap-2">
                                            <Tooltip
                                                :disabled="
                                                    !isCheckboxDisabled(
                                                        `provider-${cond.id}`,
                                                        cond.requires_scroll,
                                                        !!cond.content,
                                                    )
                                                "
                                            >
                                                <TooltipTrigger as-child>
                                                    <span>
                                                        <Checkbox
                                                            :id="`provider-${cond.id}`"
                                                            :model-value="
                                                                providerChecks[
                                                                    cond.id
                                                                ] ?? false
                                                            "
                                                            :disabled="
                                                                isCheckboxDisabled(
                                                                    `provider-${cond.id}`,
                                                                    cond.requires_scroll,
                                                                    !!cond.content,
                                                                )
                                                            "
                                                            @update:model-value="
                                                                (
                                                                    val:
                                                                        | boolean
                                                                        | 'indeterminate',
                                                                ) =>
                                                                    onProviderCheck(
                                                                        cond.id,
                                                                        val,
                                                                    )
                                                            "
                                                        />
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent side="right">
                                                    <p>
                                                        Erst lesen dann klicken
                                                    </p>
                                                </TooltipContent>
                                            </Tooltip>
                                            <Label
                                                :for="`provider-${cond.id}`"
                                                class="text-sm leading-relaxed"
                                                :class="
                                                    isCheckboxDisabled(
                                                        `provider-${cond.id}`,
                                                        cond.requires_scroll,
                                                        !!cond.content,
                                                    )
                                                        ? 'cursor-not-allowed text-muted-foreground'
                                                        : 'cursor-pointer'
                                                "
                                            >
                                                {{ cond.acknowledgement_label }}
                                                <span
                                                    v-if="cond.is_required"
                                                    class="text-destructive"
                                                    >*</span
                                                >
                                            </Label>
                                        </div>
                                    </TooltipProvider>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- No conditions message -->
                        <p
                            v-if="!hasAnyConditions"
                            class="text-sm text-muted-foreground"
                        >
                            No additional conditions to review. You may proceed
                            to payment.
                        </p>
                    </div>

                    <!-- Right: Order Summary -->
                    <div>
                        <Card class="sticky top-6">
                            <CardHeader>
                                <CardTitle>Order Summary</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div
                                    v-if="event"
                                    class="mb-2 text-sm text-muted-foreground"
                                >
                                    <span class="font-medium text-foreground">{{
                                        event.name
                                    }}</span>
                                </div>

                                <div class="space-y-2 text-sm">
                                    <div
                                        v-for="item in cartItems"
                                        :key="item.name"
                                        class="flex justify-between gap-2"
                                    >
                                        <span class="text-muted-foreground">
                                            {{ item.quantity }}x {{ item.name }}
                                            <Badge
                                                v-if="item.is_addon"
                                                variant="secondary"
                                                class="ml-1 text-[10px]"
                                                >Addon</Badge
                                            >
                                        </span>
                                        <span>{{
                                            formatPrice(item.line_total)
                                        }}</span>
                                    </div>
                                </div>

                                <div class="space-y-2 border-t pt-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground"
                                            >Subtotal</span
                                        >
                                        <span>{{ formatPrice(subtotal) }}</span>
                                    </div>
                                    <div
                                        v-if="discount > 0"
                                        class="flex justify-between text-green-600"
                                    >
                                        <span
                                            >Discount
                                            <span
                                                v-if="voucher"
                                                class="font-mono"
                                                >({{ voucher.code }})</span
                                            ></span
                                        >
                                        <span
                                            >-{{ formatPrice(discount) }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between border-t pt-2 text-base font-bold"
                                    >
                                        <span>Total</span>
                                        <span>{{ formatPrice(total) }}</span>
                                    </div>
                                </div>

                                <div class="text-sm">
                                    <span class="text-muted-foreground"
                                        >Payment:
                                    </span>
                                    <span class="font-medium">{{
                                        paymentMethodLabel
                                    }}</span>
                                </div>

                                <Button
                                    class="w-full"
                                    size="lg"
                                    :disabled="
                                        isSubmitting ||
                                        (hasAnyConditions &&
                                            !allRequiredSatisfied)
                                    "
                                    @click="submitCheckout"
                                >
                                    {{
                                        isSubmitting
                                            ? 'Processing…'
                                            : 'Confirm & Pay'
                                    }}
                                </Button>

                                <p
                                    v-if="
                                        hasAnyConditions &&
                                        !allRequiredSatisfied
                                    "
                                    class="text-center text-xs text-muted-foreground"
                                >
                                    Please accept all required conditions to
                                    proceed.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </main>

        <footer class="border-t">
            <div
                class="mx-auto max-w-4xl px-6 py-6 text-center text-sm text-muted-foreground"
            >
                Powered by LanCore
            </div>
        </footer>
    </div>
</template>
