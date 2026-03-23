<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { update } from '@/actions/App/Http/Controllers/Settings/TicketDiscoveryController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import { edit } from '@/routes/ticket-discovery'
import type { BreadcrumbItem } from '@/types'
import { X } from 'lucide-vue-next'

const props = defineProps<{
    isTicketDiscoverable: boolean
    ticketDiscoveryAllowlist: string[]
}>()

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Advanced Ticketing',
        href: edit(),
    },
]

const form = useForm({
    is_ticket_discoverable: props.isTicketDiscoverable,
    ticket_discovery_allowlist: [...props.ticketDiscoveryAllowlist],
})

const newUsername = ref('')

function addUsername(): void {
    const trimmed = newUsername.value.trim()
    if (trimmed && !form.ticket_discovery_allowlist.includes(trimmed)) {
        form.ticket_discovery_allowlist.push(trimmed)
    }
    newUsername.value = ''
}

function handleUsernameKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter') {
        event.preventDefault()
        addUsername()
    }
}

function removeUsername(index: number): void {
    form.ticket_discovery_allowlist.splice(index, 1)
}

function clearAllUsernames(): void {
    form.ticket_discovery_allowlist = []
}

watch(
    () => form.is_ticket_discoverable,
    (isDiscoverable) => {
        if (isDiscoverable) {
            form.ticket_discovery_allowlist = []
        }
    },
)

function submit(): void {
    form.patch(update().url, {
        preserveScroll: true,
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Advanced Ticketing" />

        <h1 class="sr-only">Advanced Ticketing settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Ticket Discovery"
                    description="Control who can search and find you as a Ticket Manager or Ticket User"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Discoverable toggle -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <Switch
                                id="ticket-discoverable"
                                v-model="form.is_ticket_discoverable"
                            />
                            <Label for="ticket-discoverable" class="cursor-pointer select-none">
                                Anyone can search and find me as Ticket Manager or Ticket User
                            </Label>
                        </div>

                        <div v-if="!form.is_ticket_discoverable" class="flex items-center gap-3">
                            <div class="size-[36px] shrink-0" />
                            <p class="text-sm text-muted-foreground">
                                Only the users listed below can search and find you.
                            </p>
                        </div>
                    </div>

                    <InputError :message="form.errors.is_ticket_discoverable" />

                    <!-- Allowlist management (only shown when not globally discoverable) -->
                    <div v-if="!form.is_ticket_discoverable" class="space-y-4">
                        <Label>Allowed Users</Label>

                        <div class="flex gap-2">
                            <Input
                                v-model="newUsername"
                                placeholder="Enter a username"
                                class="flex-1"
                                @keydown="handleUsernameKeydown"
                            />
                            <Button type="button" variant="outline" @click="addUsername" :disabled="!newUsername.trim()">
                                Add
                            </Button>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            Type a username and press Enter or click Add. Usernames are stored as entered and matched to platform users in the background.
                        </p>

                        <InputError :message="form.errors.ticket_discovery_allowlist" />

                        <!-- Tags -->
                        <div v-if="form.ticket_discovery_allowlist.length > 0" class="space-y-3">
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="(username, index) in form.ticket_discovery_allowlist"
                                    :key="index"
                                    variant="secondary"
                                    class="gap-1 pr-1"
                                >
                                    {{ username }}
                                    <button
                                        type="button"
                                        class="ml-1 rounded-full p-0.5 transition-colors hover:bg-muted-foreground/20"
                                        @click="removeUsername(index)"
                                    >
                                        <X class="size-3" />
                                        <span class="sr-only">Remove {{ username }}</span>
                                    </button>
                                </Badge>
                            </div>
                            <Button type="button" variant="ghost" size="sm" @click="clearAllUsernames">
                                Clear all
                            </Button>
                        </div>

                        <p v-else class="text-sm text-muted-foreground">No users added to your allow list yet.</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving…' : 'Save' }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
