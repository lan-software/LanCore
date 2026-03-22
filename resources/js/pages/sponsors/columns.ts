import { h } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import SponsorAuditController from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorAuditController'
import type { Sponsor } from '@/types/domain'

function sortableHeader(label: string) {
    return ({ column }: { column: { getToggleSortingHandler: () => ((e: Event) => void) | undefined; getIsSorted: () => false | 'asc' | 'desc' } }) =>
        h(
            Button,
            {
                variant: 'ghost',
                size: 'sm',
                class: '-ml-3 h-8 data-[state=open]:bg-accent',
                onClick: column.getToggleSortingHandler(),
            },
            () => [
                h('span', label),
                column.getIsSorted() === 'asc'
                    ? h(ArrowUp, { class: 'ml-1.5 size-3.5' })
                    : column.getIsSorted() === 'desc'
                      ? h(ArrowDown, { class: 'ml-1.5 size-3.5' })
                      : h(ArrowUpDown, { class: 'ml-1.5 size-3.5 opacity-40' }),
            ],
        )
}

export const columns: ColumnDef<Sponsor>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        id: 'sponsor_level',
        header: () => h('span', 'Level'),
        cell: ({ row }) => {
            const level = row.original.sponsor_level
            if (!level) return h('span', { class: 'text-muted-foreground' }, '—')
            return h(
                Badge,
                { variant: 'outline', style: { borderColor: level.color, color: level.color } },
                () => level.name,
            )
        },
    },
    {
        id: 'events',
        header: () => h('span', 'Events'),
        cell: ({ row }) => {
            const events = row.original.events ?? []
            if (events.length === 0) return h('span', { class: 'text-muted-foreground' }, '—')
            return h('span', events.map((e) => e.name).join(', '))
        },
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Created'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground' },
                new Date(row.getValue('created_at') as string).toLocaleDateString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                }),
            ),
    },
    {
        id: 'actions',
        header: () => h('span'),
        cell: ({ row }) =>
            h(
                Button,
                {
                    variant: 'outline',
                    size: 'sm',
                    onClick: (e: MouseEvent) => {
                        e.stopPropagation()
                        router.visit(SponsorAuditController(row.original.id).url)
                    },
                },
                () => 'Audit',
            ),
    },
]
