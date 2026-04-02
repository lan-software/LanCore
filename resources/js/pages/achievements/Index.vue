<script setup lang="ts">
import { router, Head, Link } from '@inertiajs/vue3'
import { FlexRender, getCoreRowModel, useVueTable  } from '@tanstack/vue-table'
import type {SortingState} from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight, Plus, Search } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { edit } from '@/actions/App/Domain/Achievements/Http/Controllers/AchievementController'
import { create as achievementCreate } from '@/actions/App/Domain/Achievements/Http/Controllers/AchievementController'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useDataTable  } from '@/composables/useDataTable'
import type {DataTableFilters} from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue'
import { index as achievementsRoute } from '@/routes/achievements'
import type { BreadcrumbItem } from '@/types'
import type { Achievement } from '@/types/domain'
import { columns } from './columns'

interface PaginatedAchievements {
    data: Achievement[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    achievements: PaginatedAchievements
    filters: DataTableFilters
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: achievementsRoute().url },
    { title: 'Achievements', href: achievementsRoute().url },
]

const { setSearch, toggleSort, setFilter, setPage, setPerPage } =
    useDataTable(() => achievementsRoute().url, props.filters)

const searchValue = ref(props.filters.search ?? '')

watch(searchValue, (val) => setSearch(val))

const sorting = computed<SortingState>(() =>
    props.filters.sort ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }] : [],
)

const table = useVueTable({
    get data() {
        return props.achievements.data
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.achievements.total,
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
    <Head title="Achievements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-48">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchValue"
                        placeholder="Search achievements…"
                        class="pl-8"
                    />
                </div>
                <Link :href="achievementCreate().url" as-button>
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Create Achievement
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
                        @click="router.visit(edit({ achievement: row.original.id }).url)"
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
            <div class="flex items-center justify-between border-t pt-4">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Rows per page</span>
                    <Select :model-value="String(achievements.per_page)" @update:model-value="(val: string) => setPerPage(Number(val))">
                        <SelectTrigger class="w-[70px] h-8">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="opt in perPageOptions" :key="opt" :value="String(opt)">
                                {{ opt }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="achievements.from && achievements.to">
                        {{ achievements.from }}–{{ achievements.to }} of {{ achievements.total }}
                    </span>
                </div>
                <div class="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        class="size-8"
                        :disabled="achievements.current_page <= 1"
                        @click="setPage(achievements.current_page - 1)"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        class="size-8"
                        :disabled="achievements.current_page >= achievements.last_page"
                        @click="setPage(achievements.current_page + 1)"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
