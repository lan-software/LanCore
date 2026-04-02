import { router } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import NewsCommentAuditController from '@/actions/App/Domain/News/Http/Controllers/NewsCommentAuditController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { NewsComment } from '@/types/domain';

function sortableHeader(label: string) {
    return ({
        column,
    }: {
        column: {
            getToggleSortingHandler: () => ((e: Event) => void) | undefined;
            getIsSorted: () => false | 'asc' | 'desc';
        };
    }) =>
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
        );
}

export const columns: ColumnDef<
    NewsComment & {
        article?: {
            id: number;
            title: string;
            slug: string;
            visibility: string;
            tags: string[] | null;
        };
    }
>[] = [
    {
        id: 'content',
        header: () => h('span', 'Comment'),
        cell: ({ row }) => {
            const content = row.original.content;
            const truncated =
                content.length > 120 ? content.slice(0, 120) + '…' : content;

            return h('div', { class: 'max-w-md' }, [
                h('p', { class: 'text-sm line-clamp-2' }, truncated),
                row.original.edited_at
                    ? h(
                          'span',
                          { class: 'text-xs text-muted-foreground italic' },
                          '(edited)',
                      )
                    : null,
            ]);
        },
    },
    {
        id: 'user',
        header: () => h('span', 'Author'),
        cell: ({ row }) =>
            h('span', { class: 'text-sm' }, row.original.user?.name ?? '—'),
    },
    {
        id: 'article',
        header: () => h('span', 'Article'),
        cell: ({ row }) => {
            const article = row.original.article;

            if (!article) {
                return h('span', '—');
            }

            return h('div', { class: 'space-y-0.5' }, [
                h(
                    'span',
                    { class: 'text-sm font-medium line-clamp-1' },
                    article.title,
                ),
                h(
                    Badge,
                    {
                        variant:
                            article.visibility === 'public'
                                ? 'default'
                                : article.visibility === 'internal'
                                  ? 'secondary'
                                  : 'outline',
                        class: 'text-[10px]',
                    },
                    () =>
                        article.visibility.charAt(0).toUpperCase() +
                        article.visibility.slice(1),
                ),
            ]);
        },
    },
    {
        accessorKey: 'is_approved',
        header: sortableHeader('Status'),
        cell: ({ row }) => {
            const approved = row.original.is_approved;

            return h(
                Badge,
                {
                    variant: approved ? 'default' : 'outline',
                    class: approved ? '' : 'border-amber-500 text-amber-600',
                },
                () => (approved ? 'Approved' : 'Pending'),
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Date'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-sm text-muted-foreground' },
                new Date(row.original.created_at).toLocaleDateString(
                    undefined,
                    {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                    },
                ),
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
                        e.stopPropagation();
                        router.visit(
                            NewsCommentAuditController(row.original.id).url,
                        );
                    },
                },
                () => 'Audit',
            ),
    },
];
