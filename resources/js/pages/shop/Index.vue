<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    Info,
    MapPin,
    Minus,
    Plus,
    ShoppingCart,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CartController from '@/actions/App/Domain/Shop/Http/Controllers/CartController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatCents } from '@/lib/money';
import { dashboard, login } from '@/routes';
import type { Event, TicketAddon, TicketType } from '@/types/domain';

type CartItemRef = {
    purchasable_type: string;
    purchasable_id: number;
    quantity: number;
};

type TicketTypeExtended = TicketType & {
    is_purchasable: boolean;
    remaining_quota: number;
    unavailability_reason: string | null;
};

type AddonExtended = TicketAddon & {
    requires_ticket: boolean;
};

const props = defineProps<{
    event: Event | null;
    ticketTypes: TicketTypeExtended[];
    addons: AddonExtended[];
    cartItemCount: number;
    cartItems: CartItemRef[];
}>();

function formatPrice(cents: number): string {
    return formatCents(cents);
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

const { t } = useI18n();
const addingItem = ref<number | null>(null);

const ticketTypeClass = 'App\\Domain\\Ticketing\\Models\\TicketType';
const addonClass = 'App\\Domain\\Ticketing\\Models\\Addon';

function getCartQuantity(
    purchasableType: string,
    purchasableId: number,
): number {
    const item = props.cartItems.find(
        (i) =>
            i.purchasable_type === purchasableType &&
            i.purchasable_id === purchasableId,
    );

    return item?.quantity ?? 0;
}

const ticketCartQuantities = computed(() => {
    const map: Record<number, number> = {};

    for (const tt of props.ticketTypes) {
        map[tt.id] = getCartQuantity(ticketTypeClass, tt.id);
    }

    return map;
});

const addonCartQuantities = computed(() => {
    const map: Record<number, number> = {};

    for (const addon of props.addons) {
        map[addon.id] = getCartQuantity(addonClass, addon.id);
    }

    return map;
});

function addToCart(
    purchasableType: 'ticket_type' | 'addon',
    purchasableId: number,
) {
    if (!props.event) {
        return;
    }

    addingItem.value = purchasableId;

    router.post(
        CartController.addItem().url,
        {
            purchasable_type: purchasableType,
            purchasable_id: purchasableId,
            quantity: 1,
            event_id: props.event.id,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                addingItem.value = null;
            },
        },
    );
}

function updateCartQuantity(
    purchasableType: 'ticket_type' | 'addon',
    purchasableId: number,
    delta: number,
) {
    const classType =
        purchasableType === 'ticket_type' ? ticketTypeClass : addonClass;
    const cartItem = props.cartItems.find(
        (i) =>
            i.purchasable_type === classType &&
            i.purchasable_id === purchasableId,
    );

    if (!cartItem) {
        return;
    }

    const currentQty = cartItem.quantity;
    const newQty = currentQty + delta;

    if (newQty <= 0) {
        // Find the cart item ID - we need to navigate to the cart for removal
        // Or use the addItem endpoint with the updated qty
        router.post(
            CartController.addItem().url,
            {
                purchasable_type: purchasableType,
                purchasable_id: purchasableId,
                quantity: 0,
                event_id: props.event!.id,
            },
            { preserveScroll: true },
        );

        return;
    }

    // Re-add with quantity 1 to increment, or use addItem which increments
    if (delta > 0) {
        addToCart(purchasableType, purchasableId);
    } else {
        // For decrement, we need to visit the cart or use a different approach
        // Since addItem only increments, we'll navigate users to the cart for precise control
        // But we can post with negative delta approximation using a full page reload
        router.visit(CartController.show().url);
    }
}
</script>

<template>
    <Head :title="t('shop.title')" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Header -->
        <header class="border-b">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4"
            >
                <Link href="/" class="text-lg font-semibold">LanCore</Link>
                <nav class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="CartController.show().url"
                            class="relative inline-flex items-center gap-1.5 pr-6 text-muted-foreground hover:text-foreground"
                        >
                            <ShoppingCart class="size-5" />
                            <span class="hidden sm:inline">{{
                                t('shop.cart')
                            }}</span>
                            <Badge
                                v-if="cartItemCount > 0"
                                class="absolute -top-2 -right-2 flex size-5 items-center justify-center rounded-full p-0 text-xs"
                            >
                                {{ cartItemCount }}
                            </Badge>
                        </Link>
                        <Link
                            :href="dashboard()"
                            class="text-sm text-muted-foreground hover:text-foreground"
                        >
                            {{ t('common.dashboard') }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-sm text-muted-foreground hover:text-foreground"
                            >{{ t('auth.login.button') }}</Link
                        >
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <template v-if="event">
                <div class="mx-auto max-w-5xl px-6 py-12">
                    <div class="space-y-8">
                        <!-- Event Info -->
                        <div>
                            <p
                                class="text-sm font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                {{ t('shop.ticketShop') }}
                            </p>
                            <h1 class="mt-2 text-4xl font-bold tracking-tight">
                                {{ event.name }}
                            </h1>
                        </div>

                        <div
                            class="flex flex-wrap gap-4 text-sm text-muted-foreground"
                        >
                            <div class="flex items-center gap-1.5">
                                <Calendar class="size-4" />
                                {{ formatDate(event.start_date) }}
                            </div>
                            <div
                                v-if="event.venue"
                                class="flex items-center gap-1.5"
                            >
                                <MapPin class="size-4" />
                                {{ event.venue.name }}
                            </div>
                        </div>

                        <!-- Ticket Types -->
                        <div class="space-y-4">
                            <h2 class="text-2xl font-semibold">
                                {{ t('shop.tickets') }}
                            </h2>
                            <div
                                v-if="ticketTypes.length > 0"
                                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <Card
                                    v-for="tt in ticketTypes"
                                    :key="tt.id"
                                    class="relative overflow-hidden"
                                >
                                    <!-- Unavailability Overlay -->
                                    <div
                                        v-if="
                                            !tt.is_purchasable &&
                                            tt.unavailability_reason
                                        "
                                        class="absolute inset-0 z-10 flex items-center justify-center bg-background/80 backdrop-blur-[1px]"
                                    >
                                        <Badge
                                            variant="secondary"
                                            class="px-4 py-2 text-sm font-semibold"
                                        >
                                            {{ tt.unavailability_reason }}
                                        </Badge>
                                    </div>

                                    <CardHeader>
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <CardTitle>{{ tt.name }}</CardTitle>
                                            <span class="text-lg font-bold">{{
                                                formatPrice(tt.price)
                                            }}</span>
                                        </div>
                                        <CardDescription
                                            v-if="tt.description"
                                            >{{
                                                tt.description
                                            }}</CardDescription
                                        >
                                    </CardHeader>
                                    <CardContent>
                                        <div
                                            class="space-y-1 text-sm text-muted-foreground"
                                        >
                                            <p v-if="tt.ticket_category">
                                                {{
                                                    t('shop.category', {
                                                        name: tt.ticket_category
                                                            .name,
                                                    })
                                                }}
                                            </p>
                                            <p>
                                                {{
                                                    t('shop.seatsPerUser', {
                                                        count: tt.seats_per_user,
                                                    })
                                                }}
                                            </p>
                                            <p
                                                v-if="
                                                    tt.remaining_quota !==
                                                    undefined
                                                "
                                            >
                                                {{
                                                    t('shop.remaining', {
                                                        count: tt.remaining_quota,
                                                    })
                                                }}
                                            </p>
                                        </div>
                                    </CardContent>
                                    <CardFooter v-if="$page.props.auth.user">
                                        <!-- Quantity Selector (when already in cart) -->
                                        <template
                                            v-if="
                                                ticketCartQuantities[tt.id] > 0
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="size-8"
                                                    @click="
                                                        updateCartQuantity(
                                                            'ticket_type',
                                                            tt.id,
                                                            -1,
                                                        )
                                                    "
                                                >
                                                    <Minus class="size-3" />
                                                </Button>
                                                <span
                                                    class="w-8 text-center text-sm font-medium"
                                                    >{{
                                                        ticketCartQuantities[
                                                            tt.id
                                                        ]
                                                    }}</span
                                                >
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="size-8"
                                                    :disabled="
                                                        addingItem === tt.id
                                                    "
                                                    @click="
                                                        addToCart(
                                                            'ticket_type',
                                                            tt.id,
                                                        )
                                                    "
                                                >
                                                    <Plus class="size-3" />
                                                </Button>
                                            </div>
                                        </template>
                                        <!-- Add to Cart button (not yet in cart) -->
                                        <template v-else>
                                            <Button
                                                size="sm"
                                                :disabled="
                                                    addingItem === tt.id ||
                                                    !tt.is_purchasable
                                                "
                                                @click="
                                                    addToCart(
                                                        'ticket_type',
                                                        tt.id,
                                                    )
                                                "
                                            >
                                                <Plus class="size-4" />
                                                {{
                                                    addingItem === tt.id
                                                        ? t('shop.adding')
                                                        : t('shop.addToCart')
                                                }}
                                            </Button>
                                        </template>
                                    </CardFooter>
                                </Card>
                            </div>
                            <p v-else class="text-muted-foreground">
                                {{ t('shop.noTicketsAvailable') }}
                            </p>
                        </div>

                        <!-- Addons -->
                        <div v-if="addons.length > 0" class="space-y-4">
                            <h2 class="text-2xl font-semibold">
                                {{ t('shop.addons') }}
                            </h2>
                            <div
                                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <Card v-for="addon in addons" :key="addon.id">
                                    <CardHeader>
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <CardTitle class="text-base">{{
                                                    addon.name
                                                }}</CardTitle>
                                                <TooltipProvider
                                                    v-if="addon.requires_ticket"
                                                >
                                                    <Tooltip>
                                                        <TooltipTrigger
                                                            as-child
                                                        >
                                                            <Info
                                                                class="size-4 text-muted-foreground"
                                                            />
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <p>
                                                                {{
                                                                    t(
                                                                        'shop.addonRequiresTicket',
                                                                    )
                                                                }}
                                                            </p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>
                                            </div>
                                            <span class="font-bold">{{
                                                formatPrice(addon.price)
                                            }}</span>
                                        </div>
                                        <CardDescription
                                            v-if="addon.description"
                                            >{{
                                                addon.description
                                            }}</CardDescription
                                        >
                                    </CardHeader>
                                    <CardFooter v-if="$page.props.auth.user">
                                        <!-- Quantity Selector (when already in cart) -->
                                        <template
                                            v-if="
                                                addonCartQuantities[addon.id] >
                                                0
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="size-8"
                                                    @click="
                                                        updateCartQuantity(
                                                            'addon',
                                                            addon.id,
                                                            -1,
                                                        )
                                                    "
                                                >
                                                    <Minus class="size-3" />
                                                </Button>
                                                <span
                                                    class="w-8 text-center text-sm font-medium"
                                                    >{{
                                                        addonCartQuantities[
                                                            addon.id
                                                        ]
                                                    }}</span
                                                >
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="size-8"
                                                    :disabled="
                                                        addingItem === addon.id
                                                    "
                                                    @click="
                                                        addToCart(
                                                            'addon',
                                                            addon.id,
                                                        )
                                                    "
                                                >
                                                    <Plus class="size-3" />
                                                </Button>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                :disabled="
                                                    addingItem === addon.id
                                                "
                                                @click="
                                                    addToCart('addon', addon.id)
                                                "
                                            >
                                                <Plus class="size-4" />
                                                {{
                                                    addingItem === addon.id
                                                        ? t('shop.adding')
                                                        : t('shop.addToCart')
                                                }}
                                            </Button>
                                        </template>
                                    </CardFooter>
                                </Card>
                            </div>
                        </div>

                        <!-- Login prompt -->
                        <div
                            v-if="!$page.props.auth?.user"
                            class="space-y-3 rounded-lg border p-6 text-center"
                        >
                            <p class="text-muted-foreground">
                                {{ t('shop.loginToPurchase') }}
                            </p>
                            <Button as-child>
                                <Link :href="login()">{{
                                    t('auth.login.button')
                                }}</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="flex flex-1 items-center justify-center px-6 py-24">
                    <div class="space-y-4 text-center">
                        <ShoppingCart
                            class="mx-auto size-12 text-muted-foreground"
                        />
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ t('shop.noEventsAvailable') }}
                        </h1>
                        <p class="mx-auto max-w-md text-muted-foreground">
                            {{ t('shop.noEventsDescription') }}
                        </p>
                    </div>
                </div>
            </template>
        </main>

        <div
            v-if="cartItemCount > 0"
            class="sticky bottom-0 z-20 border-t bg-background/95 px-4 py-3 backdrop-blur"
        >
            <Button as-child class="w-full" size="lg">
                <Link :href="CartController.show().url" class="gap-2">
                    <ShoppingCart class="size-4" />
                    {{ t('shop.viewCart', { count: cartItemCount }) }}
                </Link>
            </Button>
        </div>

        <footer class="border-t">
            <div
                class="mx-auto max-w-5xl px-6 py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('shop.poweredBy') }}
            </div>
        </footer>
    </div>
</template>
