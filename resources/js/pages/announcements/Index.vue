<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { index as announcementsRoute } from '@/routes/announcements'
import { create as announcementCreate } from '@/actions/App/Domain/Announcement/Http/Controllers/AnnouncementController'
import { edit } from '@/actions/App/Domain/Announcement/Http/Controllers/AnnouncementController'
import { Plus, Search, Megaphone } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import type { BreadcrumbItem } from '@/types'
import type { Announcement } from '@/types/domain'

interface PaginatedAnnouncements {
    data: Announcement[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    announcements: PaginatedAnnouncements
    filters: Record<string, string | undefined>
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: announcementsRoute().url },
    { title: 'Announcements', href: announcementsRoute().url },
]

const searchValue = ref(props.filters.search ?? '')

watch(searchValue, (val) => {
    router.get(announcementsRoute().url, { search: val || undefined }, { preserveState: true, replace: true })
})

function priorityVariant(priority: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (priority) {
        case 'emergency': return 'destructive'
        case 'normal': return 'default'
        case 'silent': return 'secondary'
        default: return 'outline'
    }
}
</script>

<template>
    <Head title="Announcements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-48">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchValue"
                        placeholder="Search announcements…"
                        class="pl-8"
                    />
                </div>
                <Link :href="announcementCreate().url" as-button>
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Create Announcement
                    </Button>
                </Link>
            </div>

            <div class="overflow-hidden rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Priority</TableHead>
                            <TableHead>Event</TableHead>
                            <TableHead>Author</TableHead>
                            <TableHead>Published</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="announcements.data.length === 0" :colspan="5">
                            <div class="flex flex-col items-center gap-2 py-8">
                                <Megaphone class="size-8 text-muted-foreground" />
                                <p class="text-sm text-muted-foreground">No announcements yet</p>
                            </div>
                        </TableEmpty>
                        <TableRow
                            v-for="announcement in announcements.data"
                            :key="announcement.id"
                            class="cursor-pointer"
                            @click="router.visit(edit({ announcement: announcement.id }).url)"
                        >
                            <TableCell class="font-medium">{{ announcement.title }}</TableCell>
                            <TableCell>
                                <Badge :variant="priorityVariant(announcement.priority)">
                                    {{ announcement.priority }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ announcement.event?.name ?? '—' }}</TableCell>
                            <TableCell>{{ announcement.author?.name ?? '—' }}</TableCell>
                            <TableCell>
                                <template v-if="announcement.published_at">
                                    {{ new Date(announcement.published_at).toLocaleDateString() }}
                                </template>
                                <Badge v-else variant="outline">Draft</Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
