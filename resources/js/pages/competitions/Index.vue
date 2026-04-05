<script setup lang="ts">
import { router, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Plus, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import CompetitionController from '@/actions/App/Domain/Competition/Http/Controllers/CompetitionController';
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
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDataTable } from '@/composables/useDataTable';
import type { DataTableFilters } from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as competitionsRoute } from '@/routes/competitions';
import type { BreadcrumbItem } from '@/types';
import type { Competition } from '@/types/domain';

interface PaginatedCompetitions {
    data: Competition[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

const props = defineProps<{
    competitions: PaginatedCompetitions;
    filters: DataTableFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: competitionsRoute().url },
    { title: 'Competitions', href: competitionsRoute().url },
];

const { filters, setSearch, setPage, setPerPage, setFilter } = useDataTable(
    () => competitionsRoute().url,
    props.filters,
);

const searchValue = ref(props.filters.search ?? '');

watch(searchValue, (val) => setSearch(val));

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    registration_open:
        'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    registration_closed:
        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    running: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
    finished:
        'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    archived: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
};

const perPageOptions = [10, 20, 50, 100];
</script>

<template>
    <Head title="Competitions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-48 flex-1">
                    <Search
                        class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                    />
                    <Input
                        v-model="searchValue"
                        placeholder="Search competitions..."
                        class="pl-8"
                    />
                </div>

                <Select
                    :model-value="filters.status ?? 'all'"
                    @update:model-value="
                        (val) => setFilter('status', val === 'all' ? undefined : val)
                    "
                >
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="registration_open"
                            >Registration Open</SelectItem
                        >
                        <SelectItem value="registration_closed"
                            >Registration Closed</SelectItem
                        >
                        <SelectItem value="running">Running</SelectItem>
                        <SelectItem value="finished">Finished</SelectItem>
                        <SelectItem value="archived">Archived</SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="String(filters.per_page ?? 20)"
                    @update:model-value="(val) => setPerPage(Number(val))"
                >
                    <SelectTrigger class="w-24">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="n in perPageOptions"
                            :key="n"
                            :value="String(n)"
                        >
                            {{ n }} / page
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Button as-child>
                    <Link :href="CompetitionController.create().url">
                        <Plus class="size-4" />
                        Create Competition
                    </Link>
                </Button>
            </div>

            <!-- Table -->
            <div
                class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="px-4">Name</TableHead>
                            <TableHead class="px-4">Type</TableHead>
                            <TableHead class="px-4">Status</TableHead>
                            <TableHead class="px-4">Teams</TableHead>
                            <TableHead class="px-4">Starts</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="competitions.data.length">
                            <TableRow
                                v-for="comp in competitions.data"
                                :key="comp.id"
                                class="cursor-pointer"
                                @click="
                                    router.visit(
                                        CompetitionController.edit(comp.id).url,
                                    )
                                "
                            >
                                <TableCell class="px-4 py-3 font-medium">
                                    {{ comp.name }}
                                </TableCell>
                                <TableCell class="px-4 py-3 capitalize">
                                    {{ comp.type }}
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <Badge
                                        variant="outline"
                                        :class="
                                            statusColors[comp.status] ?? ''
                                        "
                                    >
                                        {{
                                            comp.status
                                                .replace(/_/g, ' ')
                                                .replace(/\b\w/g, (c: string) =>
                                                    c.toUpperCase(),
                                                )
                                        }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    {{ comp.teams_count ?? 0 }}
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    {{
                                        comp.starts_at
                                            ? new Date(
                                                  comp.starts_at,
                                              ).toLocaleDateString()
                                            : '-'
                                    }}
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty v-else :colspan="5">
                            No competitions found.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div
                    class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <span class="text-xs text-muted-foreground">
                        <template
                            v-if="competitions.from && competitions.to"
                        >
                            Showing {{ competitions.from }}-{{
                                competitions.to
                            }}
                            of {{ competitions.total }}
                        </template>
                        <template v-else>
                            {{ competitions.total }} competitions
                        </template>
                    </span>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="competitions.current_page <= 1"
                            @click="setPage(competitions.current_page - 1)"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <span class="px-2 text-xs text-muted-foreground">
                            {{ competitions.current_page }} /
                            {{ competitions.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="
                                competitions.current_page >=
                                competitions.last_page
                            "
                            @click="setPage(competitions.current_page + 1)"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
