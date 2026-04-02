<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { AlertTriangle, Megaphone, ArrowLeft } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import type { Announcement } from '@/types/domain'

defineProps<{
    event: { id: number; name: string }
    announcements: Announcement[]
    dismissedIds: number[]
}>()

function priorityVariant(priority: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (priority) {
        case 'emergency': return 'destructive'
        case 'normal': return 'default'
        case 'silent': return 'secondary'
        default: return 'outline'
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <Head :title="`Announcements — ${event.name}`" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
                <span class="text-lg font-semibold">{{ event.name }}</span>
                <Link href="/" class="text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft class="inline size-4 mr-1" />
                    Back to Home
                </Link>
            </div>
        </header>

        <main class="flex-1">
            <div class="mx-auto max-w-3xl px-6 py-8">
                <div class="space-y-6">
                    <div class="flex items-center gap-2">
                        <Megaphone class="size-5 text-muted-foreground" />
                        <h1 class="text-2xl font-bold tracking-tight">All Announcements</h1>
                    </div>

                    <div v-if="announcements.length === 0" class="flex flex-col items-center gap-2 py-12">
                        <Megaphone class="size-8 text-muted-foreground" />
                        <p class="text-sm text-muted-foreground">No announcements for this event yet.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="announcement in announcements"
                            :key="announcement.id"
                            class="rounded-lg border p-4 space-y-2"
                            :class="{
                                'border-destructive/50 bg-destructive/5': announcement.priority === 'emergency',
                                'opacity-60': dismissedIds.includes(announcement.id),
                            }"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <AlertTriangle v-if="announcement.priority === 'emergency'" class="size-4 text-destructive" />
                                    <h3 class="font-semibold">{{ announcement.title }}</h3>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <Badge :variant="priorityVariant(announcement.priority)">
                                        {{ announcement.priority }}
                                    </Badge>
                                    <Badge v-if="dismissedIds.includes(announcement.id)" variant="outline">
                                        Dismissed
                                    </Badge>
                                </div>
                            </div>
                            <p v-if="announcement.description" class="text-sm text-muted-foreground">
                                {{ announcement.description }}
                            </p>
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <span v-if="announcement.published_at">{{ formatDate(announcement.published_at) }}</span>
                                <span v-if="announcement.author">· {{ announcement.author.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
