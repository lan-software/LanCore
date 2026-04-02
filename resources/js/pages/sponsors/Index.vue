<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FlexRender } from '@tanstack/vue-table';
import { Plus } from 'lucide-vue-next';
import { edit } from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorController';
import { create as sponsorCreate } from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorController';
import Heading from '@/components/Heading.vue';
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
import { useDataTable } from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as sponsorsRoute } from '@/routes/sponsors';
import type { BreadcrumbItem } from '@/types';
import type { Sponsor } from '@/types/domain';
import { columns } from './columns';

defineProps<{
    sponsors: {
        data: Sponsor[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        per_page?: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: sponsorsRoute().url },
    { title: 'Sponsors', href: sponsorsRoute().url },
];

const { setSearch, setSort, setPerPage } = useDataTable(
    () => sponsorsRoute().url,
    {},
);
</script>

<template>
    <Head title="Sponsors" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Heading title="Sponsors" description="Manage event sponsors" />
                <Link :href="sponsorCreate().url">
                    <Button>
                        <Plus class="size-4" />
                        Add Sponsor
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-4">
                <Input
                    placeholder="Search sponsors…"
                    class="max-w-sm"
                    :model-value="filters.search ?? ''"
                    @update:model-value="setSearch"
                />
                <div class="ml-auto flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Per page</span>
                    <Select
                        :model-value="String(filters.per_page ?? 20)"
                        @update:model-value="(val) => setPerPage(Number(val))"
                    >
                        <SelectTrigger class="w-20">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="20">20</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                v-for="col in columns"
                                :key="col.id ?? (col as any).accessorKey"
                            >
                                <FlexRender
                                    v-if="typeof col.header === 'function'"
                                    :render="col.header"
                                    :props="{
                                        column: {
                                            getToggleSortingHandler: () => () =>
                                                setSort(
                                                    (col as any).accessorKey,
                                                ),
                                            getIsSorted: () =>
                                                filters.sort ===
                                                (col as any).accessorKey
                                                    ? (filters.direction ??
                                                      false)
                                                    : false,
                                        },
                                    }"
                                />
                                <template v-else>{{ col.header }}</template>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="sponsor in sponsors.data"
                            :key="sponsor.id"
                            class="cursor-pointer"
                            @click="router.visit(edit(sponsor.id).url)"
                        >
                            <TableCell
                                v-for="col in columns"
                                :key="col.id ?? (col as any).accessorKey"
                            >
                                <FlexRender
                                    v-if="typeof col.cell === 'function'"
                                    :render="col.cell"
                                    :props="{
                                        row: {
                                            original: sponsor,
                                            getValue: (key: string) =>
                                                (sponsor as any)[key],
                                        },
                                        column: col,
                                    }"
                                />
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="sponsors.data.length === 0">
                            <TableCell
                                :colspan="columns.length"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No sponsors found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div
                v-if="sponsors.last_page > 1"
                class="flex items-center justify-between"
            >
                <p class="text-sm text-muted-foreground">
                    Showing {{ sponsors.data.length }} of
                    {{ sponsors.total }} sponsors
                </p>
                <div class="flex gap-1">
                    <template v-for="link in sponsors.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            variant="outline"
                            size="sm"
                            :class="{ 'bg-accent': link.active }"
                            @click="router.visit(link.url)"
                            ><span v-html="link.label"
                        /></Button>
                        <Button v-else variant="outline" size="sm" disabled
                            ><span v-html="link.label"
                        /></Button>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
