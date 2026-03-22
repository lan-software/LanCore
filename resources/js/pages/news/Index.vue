<script setup lang="ts">
import { FlexRender, getCoreRowModel, useVueTable, type SortingState } from '@tanstack/vue-table'
import { router, Head, Link } from '@inertiajs/vue3'
import { edit } from '@/actions/App/Domain/News/Http/Controllers/NewsArticleController'
import { create as newsCreate } from '@/actions/App/Domain/News/Http/Controllers/NewsArticleController'
import { ChevronLeft, ChevronRight, Plus, Search } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useDataTable, type DataTableFilters } from '@/composables/useDataTable'
import { index as newsRoute } from '@/routes/news'
import type { BreadcrumbItem } from '@/types'
import type { NewsArticle } from '@/types/domain'
import { columns } from './columns'

interface PaginatedArticles {
    data: NewsArticle[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    articles: PaginatedArticles
    filters: DataTableFilters
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: newsRoute().url },
    { title: 'News', href: newsRoute().url },
]

const { filters, setSearch, toggleSort, setFilter, setPage, setPerPage } =
    useDataTable(() => newsRoute().url, props.filters)

const searchValue = ref(props.filters.search ?? '')

watch(searchValue, (val) => setSearch(val))

const sorting = computed<SortingState>(() =>
    props.filters.sort ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }] : [],
)

const table = useVueTable({
    get data() {
        return props.articles.data
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.articles.total,
    getRowId: (row) => String(row.id),
    state: {
        get sorting() {
            return sorting.value
        },
    },
    onSortingChange: (updater) => {
        const newSorting = typeof updater === 'function' ? updater(sorting.value) : updater
        if (newSorting.length > 0) {
            toggleSort(newSorting[0].id)
        } else {
            setFilter('sort', undefined)
            setFilter('direction', undefined)
        }
    },
})

const perPageOptions = [10, 20, 50, 100]
</script>

<template>
    <Head title="News Articles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-48">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchValue"
                        placeholder="Search articles…"
                        class="pl-8"
                    />
                </div>
                <Link :href="newsCreate().url" as-button>
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Create Article
                    </Button>
                </Link>
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
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        class="cursor-pointer hover:bg-muted/50"
                        @click="router.visit(edit({ newsArticle: row.original.id }).url)"
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
                    </TableRow>
                    <TableEmpty v-if="table.getRowModel().rows.length === 0" :columns-count="table.getAllColumns().length" />
                </TableBody>
            </Table>

            <!-- Pagination -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">
                        {{ props.articles.from }}-{{ props.articles.to }} of {{ props.articles.total }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="props.articles.current_page === 1"
                        @click="setPage(props.articles.current_page - 1)"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="props.articles.current_page === props.articles.last_page"
                        @click="setPage(props.articles.current_page + 1)"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
