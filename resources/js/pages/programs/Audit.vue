<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { edit as programEdit } from '@/actions/App/Domain/Program/Http/Controllers/ProgramController'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as programsRoute } from '@/routes/programs'
import type { BreadcrumbItem } from '@/types'
import type { Audit } from '@/types/domain'

interface PaginatedAudits {
    data: Audit[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: { url: string | null; label: string; active: boolean }[]
    prev_page_url: string | null
    next_page_url: string | null
}

const props = defineProps<{
    program: { id: number; name: string }
    audits: PaginatedAudits
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: programsRoute().url },
    { title: 'Programs', href: programsRoute().url },
    { title: props.program.name, href: programEdit(props.program.id).url },
    { title: 'Audit Log', href: '#' },
]

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    })
}

function eventLabel(event: string): string {
    switch (event) {
        case 'created': return 'Created'
        case 'updated': return 'Updated'
        case 'deleted': return 'Deleted'
        case 'restored': return 'Restored'
        default: return event.charAt(0).toUpperCase() + event.slice(1)
    }
}

function eventVariant(event: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (event) {
        case 'created': return 'default'
        case 'updated': return 'outline'
        case 'deleted': return 'destructive'
        default: return 'secondary'
    }
}

function changedFields(audit: Audit): { field: string; old: unknown; new: unknown }[] {
    const fields: { field: string; old: unknown; new: unknown }[] = []
    const allKeys = new Set([...Object.keys(audit.old_values ?? {}), ...Object.keys(audit.new_values ?? {})])

    for (const key of allKeys) {
        fields.push({ field: key, old: audit.old_values?.[key] ?? null, new: audit.new_values?.[key] ?? null })
    }

    return fields
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined) {
return '—'
}

    if (typeof value === 'object') {
return JSON.stringify(value)
}

    return String(value)
}
</script>

<template>
    <Head :title="`Audit Log – ${program.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Audit Log for {{ program.name }}</h2>
                <Button variant="outline" as-child>
                    <Link :href="programEdit(program.id).url">Back to Program</Link>
                </Button>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="px-4">Date</TableHead>
                            <TableHead class="px-4">User</TableHead>
                            <TableHead class="px-4">Action</TableHead>
                            <TableHead class="px-4">Changes</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="audits.data.length">
                            <TableRow v-for="audit in audits.data" :key="audit.id">
                                <TableCell class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(audit.created_at) }}</TableCell>
                                <TableCell class="px-4 py-3 whitespace-nowrap text-sm">{{ audit.user?.name ?? 'System' }}</TableCell>
                                <TableCell class="px-4 py-3">
                                    <Badge :variant="eventVariant(audit.event)">{{ eventLabel(audit.event) }}</Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <div v-if="changedFields(audit).length" class="space-y-1">
                                        <div v-for="change in changedFields(audit)" :key="change.field" class="text-sm">
                                            <span class="font-medium">{{ change.field }}:</span>
                                            <span v-if="audit.event === 'created'" class="text-green-600 dark:text-green-400">{{ formatValue(change.new) }}</span>
                                            <template v-else>
                                                <span class="text-red-500 line-through">{{ formatValue(change.old) }}</span>
                                                <span class="mx-1">→</span>
                                                <span class="text-green-600 dark:text-green-400">{{ formatValue(change.new) }}</span>
                                            </template>
                                        </div>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">No changes recorded</span>
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty v-else :colspan="4">No audit entries found.</TableEmpty>
                    </TableBody>
                </Table>

                <div class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <span class="text-xs text-muted-foreground">
                        <template v-if="audits.from && audits.to">Showing {{ audits.from }}–{{ audits.to }} of {{ audits.total }} entries</template>
                        <template v-else>{{ audits.total }} entries</template>
                    </span>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" class="size-8 p-0" :disabled="!audits.prev_page_url" as-child>
                            <Link :href="audits.prev_page_url ?? '#'"><ChevronLeft class="size-4" /></Link>
                        </Button>
                        <span class="px-2 text-xs text-muted-foreground">{{ audits.current_page }} / {{ audits.last_page }}</span>
                        <Button variant="outline" size="sm" class="size-8 p-0" :disabled="!audits.next_page_url" as-child>
                            <Link :href="audits.next_page_url ?? '#'"><ChevronRight class="size-4" /></Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
