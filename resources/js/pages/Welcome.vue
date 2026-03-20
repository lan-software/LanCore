<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { dashboard, login, register } from '@/routes'
import { Badge } from '@/components/ui/badge'
import { Calendar, Clock, MapPin } from 'lucide-vue-next'
import type { Event } from '@/types/domain'

withDefaults(
    defineProps<{
        canRegister: boolean
        nextEvent: Event | null
    }>(),
    {
        canRegister: true,
    },
)

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

function formatTime(dateString: string): string {
    return new Date(dateString).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    })
}

function formatDateTime(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Header -->
        <header class="border-b">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <span class="text-lg font-semibold">LanCore</span>
                <nav class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Next Upcoming Event -->
            <template v-if="nextEvent">
                <div class="mx-auto max-w-5xl px-6 py-12">
                    <!-- Event Hero -->
                    <div class="space-y-6">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider text-muted-foreground">Next Event</p>
                            <h1 class="mt-2 text-4xl font-bold tracking-tight">{{ nextEvent.name }}</h1>
                        </div>

                        <!-- Banner Image -->
                        <img
                            v-if="nextEvent.banner_image_url"
                            :src="nextEvent.banner_image_url"
                            :alt="nextEvent.name"
                            class="w-full rounded-xl border object-cover max-h-80"
                        />

                        <!-- Key Details Grid -->
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <!-- Date & Time -->
                            <div class="flex items-start gap-3 rounded-lg border p-4">
                                <Calendar class="mt-0.5 size-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Date & Time</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDate(nextEvent.start_date) }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatTime(nextEvent.start_date) }} – {{ formatTime(nextEvent.end_date) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Venue -->
                            <div v-if="nextEvent.venue" class="flex items-start gap-3 rounded-lg border p-4">
                                <MapPin class="mt-0.5 size-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">{{ nextEvent.venue.name }}</p>
                                    <template v-if="nextEvent.venue.address">
                                        <p class="text-sm text-muted-foreground">{{ nextEvent.venue.address.street }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ nextEvent.venue.address.zip_code }} {{ nextEvent.venue.address.city }}
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <p v-if="nextEvent.description" class="text-muted-foreground leading-relaxed">
                            {{ nextEvent.description }}
                        </p>

                        <!-- Programs & Time Slots -->
                        <div v-if="nextEvent.programs && nextEvent.programs.length > 0" class="space-y-6">
                            <h2 class="text-2xl font-semibold">Program</h2>

                            <div v-for="program in nextEvent.programs" :key="program.id" class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-medium">{{ program.name }}</h3>
                                    <Badge v-if="nextEvent.primary_program_id === program.id" variant="default">Primary</Badge>
                                </div>
                                <p v-if="program.sponsors && program.sponsors.length > 0" class="text-sm italic text-muted-foreground">
                                    presented by {{ program.sponsors.map((s: { name: string }) => s.name).join(', ') }}
                                </p>
                                <p v-if="program.description" class="text-sm text-muted-foreground">
                                    {{ program.description }}
                                </p>

                                <!-- Time Slots -->
                                <div v-if="program.time_slots && program.time_slots.length > 0" class="rounded-lg border">
                                    <div
                                        v-for="(slot, index) in program.time_slots"
                                        :key="slot.id"
                                        class="flex items-start gap-3 p-4"
                                        :class="{ 'border-t': index > 0 }"
                                    >
                                        <Clock class="mt-0.5 size-4 text-muted-foreground" />
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium">{{ slot.name }}</span>
                                                <span class="text-xs text-muted-foreground">{{ formatDateTime(slot.starts_at) }}</span>
                                            </div>
                                            <p v-if="slot.sponsors && slot.sponsors.length > 0" class="text-xs italic text-muted-foreground">
                                                presented by {{ slot.sponsors.map((s: { name: string }) => s.name).join(', ') }}
                                            </p>
                                            <p v-if="slot.description" class="mt-1 text-sm text-muted-foreground">
                                                {{ slot.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Venue Images -->
                        <div v-if="nextEvent.venue?.images && nextEvent.venue.images.length > 0" class="space-y-4">
                            <h2 class="text-2xl font-semibold">Venue</h2>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <img
                                    v-for="image in nextEvent.venue.images"
                                    :key="image.id"
                                    :src="image.url"
                                    :alt="image.alt_text ?? nextEvent.venue.name"
                                    class="rounded-lg border object-cover aspect-video w-full"
                                />
                            </div>
                        </div>

                        <!-- Sponsors -->
                        <div v-if="nextEvent.sponsors && nextEvent.sponsors.length > 0" class="space-y-4">
                            <h2 class="text-2xl font-semibold">Sponsors</h2>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <a
                                    v-for="sponsor in nextEvent.sponsors"
                                    :key="sponsor.id"
                                    :href="sponsor.link ?? undefined"
                                    :target="sponsor.link ? '_blank' : undefined"
                                    :rel="sponsor.link ? 'noopener noreferrer' : undefined"
                                    class="flex flex-col items-center gap-3 rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                    :class="{ 'cursor-default': !sponsor.link }"
                                >
                                    <img
                                        v-if="sponsor.logo_url"
                                        :src="sponsor.logo_url"
                                        :alt="sponsor.name"
                                        class="h-16 w-auto object-contain"
                                    />
                                    <div class="text-center">
                                        <p class="text-sm font-medium">{{ sponsor.name }}</p>
                                        <Badge
                                            v-if="sponsor.sponsor_level"
                                            variant="outline"
                                            :style="{
                                                borderColor: sponsor.sponsor_level.color,
                                                color: sponsor.sponsor_level.color,
                                            }"
                                            class="mt-1"
                                        >
                                            {{ sponsor.sponsor_level.name }}
                                        </Badge>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- No Upcoming Event -->
            <template v-else>
                <div class="flex flex-1 items-center justify-center px-6 py-24">
                    <div class="text-center space-y-4">
                        <Calendar class="mx-auto size-12 text-muted-foreground" />
                        <h1 class="text-3xl font-bold tracking-tight">No Upcoming Events</h1>
                        <p class="text-muted-foreground max-w-md mx-auto">
                            There are currently no upcoming events scheduled. Check back later for updates.
                        </p>
                    </div>
                </div>
            </template>
        </main>

        <!-- Footer -->
        <footer class="border-t">
            <div class="mx-auto max-w-5xl px-6 py-6 text-center text-sm text-muted-foreground">
                Powered by LanCore
            </div>
        </footer>
    </div>
</template>
