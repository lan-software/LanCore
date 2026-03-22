import { h } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import NewsArticleAuditController from '@/actions/App/Domain/News/Http/Controllers/NewsArticleAuditController'
import type { NewsArticle } from '@/types/domain'

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

const visibilityVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    public: 'default',
    internal: 'secondary',
    draft: 'outline',
}

export const columns: ColumnDef<NewsArticle>[] = [
    {
        accessorKey: 'title',
        header: sortableHeader('Title'),
        cell: ({ row }) => {
            const article = row.original
            return h('div', { class: 'space-y-1' }, [
                h('span', { class: 'font-medium' }, row.getValue('title')),
                article.is_archived ? h(Badge, { variant: 'outline', class: 'ml-2 text-xs' }, () => 'Archived') : null,
            ])
        },
    },
    {
        id: 'author',
        header: () => h('span', 'Author'),
        cell: ({ row }) => h('span', row.original.author?.name ?? '—'),
    },
    {
        accessorKey: 'visibility',
        header: sortableHeader('Visibility'),
        cell: ({ row }) => {
            const visibility = row.getValue('visibility') as string
            return h(
                Badge,
                { variant: visibilityVariant[visibility] ?? 'secondary' },
                () => visibility.charAt(0).toUpperCase() + visibility.slice(1),
            )
        },
    },
    {
        accessorKey: 'published_at',
        header: sortableHeader('Published'),
        cell: ({ row }) => {
            const date = row.getValue('published_at') as string | null
            return h(
                'span',
                { class: 'text-muted-foreground' },
                date
                    ? new Date(date).toLocaleDateString(undefined, {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric',
                      })
                    : '—',
            )
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
                        router.visit(NewsArticleAuditController(row.original.id).url)
                    },
                },
                () => 'Audit',
            ),
    },
]
