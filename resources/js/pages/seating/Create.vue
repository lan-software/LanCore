<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3'
import SeatPlanController from '@/actions/App/Domain/Seating/Http/Controllers/SeatPlanController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as seatPlansRoute } from '@/routes/seat-plans'
import type { BreadcrumbItem } from '@/types'

defineProps<{
    events: { id: number; name: string }[]
    selectedEventId?: number | null
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: seatPlansRoute().url },
    { title: 'Seat Plans', href: seatPlansRoute().url },
    { title: 'Create', href: SeatPlanController.create().url },
]
</script>

<template>
    <Head title="Create Seat Plan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <!-- Back link -->
            <div>
                <Link
                    :href="seatPlansRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Seat Plans
                </Link>
            </div>

            <Form
                v-bind="SeatPlanController.store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Seat Plan Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Seat Plan Information"
                        description="Provide the basic details for this seat plan"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="e.g. Main Hall"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="event_id">Event</Label>
                        <Select name="event_id" :default-value="selectedEventId ? String(selectedEventId) : undefined">
                            <SelectTrigger>
                                <SelectValue placeholder="Select an event" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="event in events"
                                    :key="event.id"
                                    :value="String(event.id)"
                                >
                                    {{ event.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.event_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="data">Seat Plan Data (JSON)</Label>
                        <Textarea
                            id="data"
                            name="data"
                            rows="12"
                            class="font-mono text-sm"
                            :default-value="JSON.stringify({ blocks: [] }, null, 2)"
                            placeholder='{"blocks": []}'
                        />
                        <p class="text-xs text-muted-foreground">JSON describing blocks, seats, and labels for the seat plan.</p>
                        <InputError :message="errors.data" />
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Creating…' : 'Create Seat Plan' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
