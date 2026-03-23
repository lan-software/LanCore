<script setup lang="ts">
import CartController from '@/actions/App/Domain/Shop/Http/Controllers/CartController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import type { Event } from '@/types/domain'
import { Head, Link, router } from '@inertiajs/vue3'
import { index as shopIndex } from '@/routes/shop'
import { Minus, Plus, ShoppingCart, Tag, Trash2, X } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type CartItemData = {
    id: number
    purchasable_type: string
    purchasable_id: number
    quantity: number
    name: string
    description: string | null
    unit_price: number
    max_quantity: number
    line_total: number
    is_addon: boolean
}

type PaymentMethodOption = {
    value: string
    label: string
    requires_redirect: boolean
}

const props = defineProps<{
    cartItems: CartItemData[]
    event: Event | null
    voucher: {
        code: string
        type: string
        discount_amount: number | null
        discount_percent: number | null
        discount: number
    } | null
    subtotal: number
    discount: number
    total: number
    dependencyErrors: string[]
    paymentMethods: PaymentMethodOption[]
}>()

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2) + ' €'
}

const voucherInput = ref('')
const isCheckingOut = ref(false)
const selectedPaymentMethod = ref(props.paymentMethods[0]?.value ?? '')

function updateQuantity(item: CartItemData, newQty: number) {
    if (newQty < 1) {
        removeItem(item)
        return
    }

    router.patch(
        CartController.updateItem(item.id).url,
        { quantity: Math.min(newQty, item.max_quantity) },
        { preserveScroll: true },
    )
}

function removeItem(item: CartItemData) {
    router.delete(CartController.removeItem(item.id).url, {
        preserveScroll: true,
    })
}

function applyVoucher() {
    if (!voucherInput.value) return

    router.post(
        CartController.applyVoucher().url,
        { voucher_code: voucherInput.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                voucherInput.value = ''
            },
        },
    )
}

function removeVoucher() {
    router.delete(CartController.removeVoucher().url, {
        preserveScroll: true,
    })
}

function checkout() {
    isCheckingOut.value = true
    router.post(CartController.reviewCheckout().url, { payment_method: selectedPaymentMethod.value }, {
        onFinish: () => {
            isCheckingOut.value = false
        },
    })
}

const ticketItems = computed(() => props.cartItems.filter((i) => !i.is_addon))
const addonItems = computed(() => props.cartItems.filter((i) => i.is_addon))
</script>

<template>
    <Head title="Your Cart" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Header -->
        <header class="border-b">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <Link href="/" class="text-lg font-semibold">LanCore</Link>
                <nav class="flex items-center gap-4">
                    <Link :href="shopIndex().url" class="text-sm text-muted-foreground hover:text-foreground">
                        &larr; Continue Shopping
                    </Link>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <div class="mx-auto max-w-5xl px-6 py-12">
                <div class="flex items-center gap-3 mb-8">
                    <ShoppingCart class="size-8" />
                    <h1 class="text-3xl font-bold tracking-tight">Your Cart</h1>
                </div>

                <template v-if="cartItems.length > 0">
                    <!-- Dependency Errors -->
                    <div v-if="dependencyErrors.length > 0" class="mb-6 rounded-lg border border-destructive/50 bg-destructive/10 p-4">
                        <p v-for="error in dependencyErrors" :key="error" class="text-sm text-destructive">
                            {{ error }}
                        </p>
                    </div>

                    <div class="grid gap-8 lg:grid-cols-3">
                        <!-- Cart Items -->
                        <div class="lg:col-span-2 space-y-4">
                            <div v-if="event" class="text-sm text-muted-foreground mb-2">
                                Event: <span class="font-medium text-foreground">{{ event.name }}</span>
                            </div>

                            <!-- Ticket Items -->
                            <div v-if="ticketItems.length > 0" class="space-y-3">
                                <h2 class="text-lg font-semibold">Tickets</h2>
                                <div
                                    v-for="item in ticketItems"
                                    :key="item.id"
                                    class="flex items-center gap-4 rounded-lg border p-4"
                                >
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-medium">{{ item.name }}</h3>
                                        <p v-if="item.description" class="text-sm text-muted-foreground truncate">{{ item.description }}</p>
                                        <p class="text-sm text-muted-foreground">{{ formatPrice(item.unit_price) }} each</p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Button variant="outline" size="icon" class="size-8" @click="updateQuantity(item, item.quantity - 1)">
                                            <Minus class="size-3" />
                                        </Button>
                                        <span class="w-8 text-center text-sm font-medium">{{ item.quantity }}</span>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            class="size-8"
                                            :disabled="item.quantity >= item.max_quantity"
                                            @click="updateQuantity(item, item.quantity + 1)"
                                        >
                                            <Plus class="size-3" />
                                        </Button>
                                    </div>

                                    <div class="w-24 text-right font-medium">
                                        {{ formatPrice(item.line_total) }}
                                    </div>

                                    <Button variant="ghost" size="icon" class="size-8 text-muted-foreground hover:text-destructive" @click="removeItem(item)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Addon Items -->
                            <div v-if="addonItems.length > 0" class="space-y-3">
                                <h2 class="text-lg font-semibold">Addons</h2>
                                <div
                                    v-for="item in addonItems"
                                    :key="item.id"
                                    class="flex items-center gap-4 rounded-lg border p-4"
                                >
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-medium">{{ item.name }}</h3>
                                            <Badge variant="secondary" class="text-xs">Addon</Badge>
                                        </div>
                                        <p v-if="item.description" class="text-sm text-muted-foreground truncate">{{ item.description }}</p>
                                        <p class="text-sm text-muted-foreground">{{ formatPrice(item.unit_price) }} each</p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Button variant="outline" size="icon" class="size-8" @click="updateQuantity(item, item.quantity - 1)">
                                            <Minus class="size-3" />
                                        </Button>
                                        <span class="w-8 text-center text-sm font-medium">{{ item.quantity }}</span>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            class="size-8"
                                            :disabled="item.quantity >= item.max_quantity"
                                            @click="updateQuantity(item, item.quantity + 1)"
                                        >
                                            <Plus class="size-3" />
                                        </Button>
                                    </div>

                                    <div class="w-24 text-right font-medium">
                                        {{ formatPrice(item.line_total) }}
                                    </div>

                                    <Button variant="ghost" size="icon" class="size-8 text-muted-foreground hover:text-destructive" @click="removeItem(item)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary Sidebar -->
                        <div>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Order Summary</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">Subtotal</span>
                                            <span>{{ formatPrice(subtotal) }}</span>
                                        </div>
                                        <div v-if="discount > 0" class="flex justify-between text-green-600">
                                            <span>Discount</span>
                                            <span>-{{ formatPrice(discount) }}</span>
                                        </div>
                                        <div class="flex justify-between border-t pt-2 text-base font-bold">
                                            <span>Total</span>
                                            <span>{{ formatPrice(total) }}</span>
                                        </div>
                                    </div>

                                    <!-- Voucher Section -->
                                    <div class="space-y-2">
                                        <template v-if="voucher">
                                            <div class="flex items-center justify-between rounded-md border border-green-200 bg-green-50 px-3 py-2 dark:border-green-800 dark:bg-green-950">
                                                <div class="flex items-center gap-2">
                                                    <Tag class="size-4 text-green-600" />
                                                    <span class="text-sm font-mono font-medium text-green-700 dark:text-green-400">{{ voucher.code }}</span>
                                                </div>
                                                <Button variant="ghost" size="icon" class="size-6" @click="removeVoucher">
                                                    <X class="size-3" />
                                                </Button>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <Label for="voucher_code">Voucher Code</Label>
                                            <div class="flex gap-2">
                                                <Input
                                                    v-model="voucherInput"
                                                    id="voucher_code"
                                                    placeholder="Enter code"
                                                    class="font-mono"
                                                    @keyup.enter="applyVoucher"
                                                />
                                                <Button variant="outline" size="sm" @click="applyVoucher">Apply</Button>
                                            </div>
                                            <InputError :message="$page.props.errors?.voucher_code" />
                                        </template>
                                    </div>

                                    <InputError :message="$page.props.errors?.cart" />

                                    <!-- Payment Method -->
                                    <div class="space-y-2">
                                        <Label>Payment Method</Label>
                                        <Select v-model="selectedPaymentMethod">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select payment method" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="method in paymentMethods"
                                                    :key="method.value"
                                                    :value="method.value"
                                                >
                                                    {{ method.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="$page.props.errors?.payment_method" />
                                    </div>

                                    <Button
                                        class="w-full"
                                        size="lg"
                                        :disabled="isCheckingOut || dependencyErrors.length > 0 || !selectedPaymentMethod"
                                        @click="checkout"
                                    >
                                        {{ isCheckingOut ? 'Processing…' : 'Proceed to Payment' }}
                                    </Button>

                                    <p v-if="paymentMethods.find(m => m.value === selectedPaymentMethod)?.requires_redirect" class="text-xs text-center text-muted-foreground">
                                        You will be redirected to complete payment.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </template>

                <!-- Empty Cart -->
                <template v-else>
                    <div class="flex flex-col items-center justify-center py-24 space-y-4">
                        <ShoppingCart class="size-16 text-muted-foreground" />
                        <h2 class="text-2xl font-bold">Your cart is empty</h2>
                        <p class="text-muted-foreground">Browse our ticket shop and add items to your cart.</p>
                        <Button as-child>
                            <Link :href="shopIndex().url">Browse Shop</Link>
                        </Button>
                    </div>
                </template>
            </div>
        </main>

        <footer class="border-t">
            <div class="mx-auto max-w-5xl px-6 py-6 text-center text-sm text-muted-foreground">
                Powered by LanCore
            </div>
        </footer>
    </div>
</template>
