<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3';
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import type { SortingState } from '@tanstack/vue-table';
import {
    ChevronLeft,
    ChevronRight,
    Check,
    Pencil,
    Search,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import {
    approve,
    destroy,
    update,
} from '@/actions/App/Domain/News/Http/Controllers/NewsCommentController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useDataTable } from '@/composables/useDataTable';
import type { DataTableFilters } from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as commentsRoute } from '@/routes/news/comments';
import type { BreadcrumbItem } from '@/types';
import type { NewsComment } from '@/types/domain';
import { columns } from './columns';

interface PaginatedComments {
    data: NewsComment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

const props = defineProps<{
    comments: PaginatedComments;
    articles: { id: number; title: string }[];
    tags: string[];
    filters: DataTableFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: commentsRoute().url },
    { title: 'News', href: commentsRoute().url },
    { title: 'Comments', href: commentsRoute().url },
];

const { filters, setSearch, toggleSort, setFilter, setPage, setPerPage } =
    useDataTable(() => commentsRoute().url, props.filters);

const searchValue = ref(props.filters.search ?? '');

watch(searchValue, (val) => setSearch(val));

const sorting = computed<SortingState>(() =>
    props.filters.sort
        ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }]
        : [],
);

const table = useVueTable({
    get data() {
        return props.comments.data;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.comments.total,
    getRowId: (row) => String(row.id),
    state: {
        get sorting() {
            return sorting.value;
        },
    },
    onSortingChange: (updater) => {
        const newSorting =
            typeof updater === 'function' ? updater(sorting.value) : updater;

        if (newSorting.length > 0) {
            toggleSort(newSorting[0].id);
        } else {
            setFilter('sort', undefined);
            setFilter('direction', undefined);
        }
    },
});

// Inline editing state
const editingId = ref<number | null>(null);
const editContent = ref('');

function startEdit(comment: NewsComment) {
    editingId.value = comment.id;
    editContent.value = comment.content;
}

function cancelEdit() {
    editingId.value = null;
    editContent.value = '';
}

function saveEdit(comment: NewsComment) {
    router.patch(
        update({ newsComment: comment.id }).url,
        { content: editContent.value },
        {
            preserveScroll: true,
            onSuccess: () => cancelEdit(),
        },
    );
}

function approveComment(comment: NewsComment) {
    router.post(
        approve({ newsComment: comment.id }).url,
        {},
        { preserveScroll: true },
    );
}

function deleteComment(comment: NewsComment) {
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }

    router.delete(destroy({ newsComment: comment.id }).url, {
        preserveScroll: true,
    });
}

const perPageOptions = [10, 20, 50, 100];
</script>

<template>
    <Head title="Comments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Search -->
                <div class="relative min-w-48 flex-1">
                    <Search
                        class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                    />
                    <Input
                        v-model="searchValue"
                        placeholder="Search comments or authors…"
                        class="pl-8"
                    />
                </div>

                <!-- Article filter -->
                <Select
                    :model-value="(filters.article_id as string) ?? 'all'"
                    @update:model-value="
                        (val) =>
                            setFilter(
                                'article_id',
                                val === 'all' ? undefined : val,
                            )
                    "
                >
                    <SelectTrigger class="w-48">
                        <SelectValue placeholder="All articles" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All articles</SelectItem>
                        <SelectItem
                            v-for="article in articles"
                            :key="article.id"
                            :value="String(article.id)"
                        >
                            {{ article.title }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Approval filter -->
                <Select
                    :model-value="
                        filters.is_approved !== undefined
                            ? String(filters.is_approved)
                            : 'all'
                    "
                    @update:model-value="
                        (val) =>
                            setFilter(
                                'is_approved',
                                val === 'all' ? undefined : val,
                            )
                    "
                >
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        <SelectItem value="1">Approved</SelectItem>
                        <SelectItem value="0">Pending</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Visibility filter -->
                <Select
                    :model-value="(filters.visibility as string) ?? 'all'"
                    @update:model-value="
                        (val) =>
                            setFilter(
                                'visibility',
                                val === 'all' ? undefined : val,
                            )
                    "
                >
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All visibility" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All visibility</SelectItem>
                        <SelectItem value="public">Public</SelectItem>
                        <SelectItem value="internal">Internal</SelectItem>
                        <SelectItem value="draft">Draft</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Tag filter -->
                <Select
                    :model-value="(filters.tag as string) ?? 'all'"
                    @update:model-value="
                        (val) =>
                            setFilter('tag', val === 'all' ? undefined : val)
                    "
                >
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All tags" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All tags</SelectItem>
                        <SelectItem v-for="tag in tags" :key="tag" :value="tag">
                            {{ tag }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Per-page selector -->
                <Select
                    :model-value="String(filters.per_page ?? 20)"
                    @update:model-value="(val) => setPerPage(Number(val))"
                >
                    <SelectTrigger class="w-24">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="opt in perPageOptions"
                            :key="opt"
                            :value="String(opt)"
                        >
                            {{ opt }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Table -->
            <Table class="border">
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="header in table.getFlatHeaders()"
                            :key="header.id"
                            class="border-r last:border-r-0"
                        >
                            <FlexRender
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                        <TableHead class="w-28">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                    >
                        <!-- Normal row -->
                        <TableRow
                            v-if="editingId !== row.original.id"
                            class="hover:bg-muted/50"
                        >
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                                class="border-r last:border-r-0"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-1">
                                    <Button
                                        v-if="!row.original.is_approved"
                                        variant="ghost"
                                        size="icon"
                                        class="size-7"
                                        title="Approve"
                                        @click="approveComment(row.original)"
                                    >
                                        <Check
                                            class="size-3.5 text-green-600"
                                        />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7"
                                        title="Edit"
                                        @click="startEdit(row.original)"
                                    >
                                        <Pencil class="size-3.5" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-7"
                                        title="Delete"
                                        @click="deleteComment(row.original)"
                                    >
                                        <Trash2
                                            class="size-3.5 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Editing row -->
                        <TableRow v-else class="bg-muted/30">
                            <TableCell
                                :colspan="table.getAllColumns().length + 1"
                                class="p-4"
                            >
                                <div class="space-y-3">
                                    <div
                                        class="flex items-center gap-2 text-sm text-muted-foreground"
                                    >
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                row.original.user?.name ??
                                                'Unknown'
                                            }}</span
                                        >
                                        <span>on</span>
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                row.original.article?.title ??
                                                'Unknown article'
                                            }}</span
                                        >
                                    </div>
                                    <Textarea
                                        v-model="editContent"
                                        rows="3"
                                        class="w-full"
                                    />
                                    <div class="flex items-center gap-2">
                                        <Button
                                            size="sm"
                                            @click="saveEdit(row.original)"
                                        >
                                            <Check class="mr-1.5 size-3.5" />
                                            Save
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="cancelEdit"
                                        >
                                            <X class="mr-1.5 size-3.5" />
                                            Cancel
                                        </Button>
                                        <Badge
                                            v-if="!row.original.is_approved"
                                            variant="outline"
                                            class="border-amber-500 text-amber-600"
                                            >Pending approval</Badge
                                        >
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                    <TableRow v-if="table.getRowModel().rows.length === 0">
                        <TableCell
                            :colspan="table.getAllColumns().length + 1"
                            class="h-24 text-center text-muted-foreground"
                        >
                            No comments found.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <!-- Pagination -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">
                        {{ props.comments.from }}-{{ props.comments.to }} of
                        {{ props.comments.total }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="props.comments.current_page === 1"
                        @click="setPage(props.comments.current_page - 1)"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="
                            props.comments.current_page ===
                            props.comments.last_page
                        "
                        @click="setPage(props.comments.current_page + 1)"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
