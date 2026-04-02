<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3'
import { Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import VoucherController from '@/actions/App/Domain/Shop/Http/Controllers/VoucherController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as vouchersIndex } from '@/routes/vouchers'
import type { BreadcrumbItem } from '@/types'
import type { Voucher } from '@/types/domain'

const props = defineProps<{
    voucher: Voucher
    events: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: vouchersIndex().url },
    { title: 'Vouchers', href: vouchersIndex().url },
    { title: props.voucher.code, href: VoucherController.edit(props.voucher.id).url },
]

const showDeleteDialog = ref(false)

function formatDateTimeLocal(dateString: string | null): string {
    if (!dateString) {
        return ''
    }

    const date = new Date(dateString)
    const pad = (n: number) => String(n).padStart(2, '0')

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function executeDelete() {
    router.delete(VoucherController.destroy(props.voucher.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false
        },
    })
}
</script>

<template>
    <Head :title="`Edit ${voucher.code}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <div>
                <Link :href="vouchersIndex().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Vouchers
                </Link>
            </div>

            <Form
                v-bind="VoucherController.update.form(voucher.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="space-y-4">
                    <Heading variant="small" title="Voucher Information" description="Update voucher details" />

                    <div class="grid gap-2">
                        <Label for="code">Code</Label>
                        <Input id="code" name="code" :default-value="voucher.code" required class="font-mono" />
                        <InputError :message="errors.code" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="type">Discount Type</Label>
                        <Select name="type" :default-value="voucher.type">
                            <SelectTrigger>
                                <SelectValue placeholder="Select discount type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="fixed_amount">Fixed Amount</SelectItem>
                                <SelectItem value="percentage">Percentage</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.type" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="discount_amount">Discount Amount (cents)</Label>
                            <Input id="discount_amount" name="discount_amount" type="number" min="0" :default-value="voucher.discount_amount !== null ? String(voucher.discount_amount) : ''" />
                            <InputError :message="errors.discount_amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="discount_percent">Discount Percent</Label>
                            <Input id="discount_percent" name="discount_percent" type="number" min="0" max="100" :default-value="voucher.discount_percent !== null ? String(voucher.discount_percent) : ''" />
                            <InputError :message="errors.discount_percent" />
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Usage Limits" description="Update usage and validity constraints" />

                    <div class="grid gap-2">
                        <Label for="max_uses">Max Uses</Label>
                        <Input id="max_uses" name="max_uses" type="number" min="1" :default-value="voucher.max_uses !== null ? String(voucher.max_uses) : ''" placeholder="Leave empty for unlimited" />
                        <InputError :message="errors.max_uses" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="valid_from">Valid From</Label>
                            <Input id="valid_from" name="valid_from" type="datetime-local" :default-value="formatDateTimeLocal(voucher.valid_from)" />
                            <InputError :message="errors.valid_from" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="valid_until">Valid Until</Label>
                            <Input id="valid_until" name="valid_until" type="datetime-local" :default-value="formatDateTimeLocal(voucher.valid_until)" />
                            <InputError :message="errors.valid_until" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_active" name="is_active" :default-value="voucher.is_active" />
                        <Label for="is_active" class="cursor-pointer">Active</Label>
                    </div>
                    <InputError :message="errors.is_active" />
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Association" description="Optionally limit to a specific event" />

                    <div class="grid gap-2">
                        <Label for="event_id">Event</Label>
                        <Select name="event_id" :default-value="voucher.event_id ? String(voucher.event_id) : undefined">
                            <SelectTrigger>
                                <SelectValue placeholder="All events (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="event in events" :key="event.id" :value="String(event.id)">
                                    {{ event.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.event_id" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                    <p v-if="recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                </div>
            </Form>

            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">Delete Voucher</h3>
                        <p class="text-sm text-muted-foreground">Permanently delete this voucher.</p>
                    </div>
                    <Button variant="destructive" size="sm" @click="showDeleteDialog = true">
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>
        </div>

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete voucher "{{ voucher.code }}"?</DialogTitle>
                    <DialogDescription>This action cannot be undone.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="executeDelete">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
