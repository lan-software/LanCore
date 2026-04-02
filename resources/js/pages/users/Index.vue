<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3'
import { FlexRender, getCoreRowModel, useVueTable  } from '@tanstack/vue-table'
import type {SortingState} from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight, Search, Shield, Trash2, X } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import UserController from '@/actions/App/Http/Controllers/Users/UserController'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useDataTable  } from '@/composables/useDataTable'
import type {DataTableFilters} from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue'
import { index as usersRoute, bulk_assign_role as usersBulkAssignRoleRoute, bulk_destroy as usersBulkDestroyRoute } from '@/routes/users'
import type { BreadcrumbItem } from '@/types'
import type { User } from '@/types/auth'
import { columns } from './columns'

interface PaginatedUsers {
    data: User[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    users: PaginatedUsers
    filters: DataTableFilters
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: usersRoute().url },
    { title: 'Users', href: usersRoute().url },
]

const { filters, rowSelection, selectedCount, selectedIds, setSearch, toggleSort, setFilter, setPage, setPerPage, updateRowSelection, clearSelection } =
    useDataTable(() => usersRoute().url, props.filters)

const searchValue = ref(props.filters.search ?? '')

watch(searchValue, (val) => setSearch(val))

const sorting = computed<SortingState>(() =>
    props.filters.sort ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }] : [],
)

const table = useVueTable({
    get data() {
        return props.users.data
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.users.total,
    enableRowSelection: true,
    getRowId: (row) => String(row.id),
    state: {
        get sorting() {
            return sorting.value
        },
        get rowSelection() {
            return rowSelection.value
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
    onRowSelectionChange: updateRowSelection,
})

// Delete confirmation dialog
const showDeleteDialog = ref(false)

function confirmDelete() {
    showDeleteDialog.value = true
}

function executeDelete() {
    router.delete(usersBulkDestroyRoute().url, {
        data: { ids: selectedIds.value },
        preserveScroll: true,
        onSuccess: () => {
            clearSelection()
            showDeleteDialog.value = false
        },
    })
}

// Assign role dialog
const showAssignRoleDialog = ref(false)
const pendingRole = ref<{ value: string; label: string } | null>(null)

function confirmAssignRole(role: { value: string; label: string }) {
    pendingRole.value = role
    showAssignRoleDialog.value = true
}

function executeAssignRole() {
    if (!pendingRole.value) {
        return
    }

    router.patch(usersBulkAssignRoleRoute().url, {
        ids: selectedIds.value,
        role: pendingRole.value.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            clearSelection()
            showAssignRoleDialog.value = false
            pendingRole.value = null
        },
    })
}

const roleOptions = [
    { value: 'user', label: 'User' },
    { value: 'admin', label: 'Admin' },
    { value: 'superadmin', label: 'Superadmin' },
]

const perPageOptions = [10, 20, 50, 100]
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Search -->
                <div class="relative flex-1 min-w-48">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchValue"
                        placeholder="Search users…"
                        class="pl-8"
                    />
                </div>

                <!-- Role filter -->
                <Select
                    :model-value="filters.role ?? 'all'"
                    @update:model-value="(val) => setFilter('role', val === 'all' ? undefined : val)"
                >
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All roles" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All roles</SelectItem>
                        <SelectItem
                            v-for="option in roleOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
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
                            v-for="n in perPageOptions"
                            :key="n"
                            :value="String(n)"
                        >
                            {{ n }} / page
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Mass-action toolbar (visible when rows selected) -->
                <template v-if="selectedCount > 0">
                    <div class="flex items-center gap-2 rounded-md border border-dashed px-3 py-1.5 text-sm">
                        <span class="text-muted-foreground">{{ selectedCount }} selected</span>

                        <!-- Assign Role dropdown -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 gap-1.5"
                                >
                                    <Shield class="size-3.5" />
                                    Assign role
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start">
                                <DropdownMenuLabel>Assign role to {{ selectedCount }} user{{ selectedCount !== 1 ? 's' : '' }}</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    v-for="option in roleOptions"
                                    :key="option.value"
                                    @click="confirmAssignRole(option)"
                                >
                                    {{ option.label }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button
                            variant="destructive"
                            size="sm"
                            class="h-7 gap-1.5"
                            @click="confirmDelete"
                        >
                            <Trash2 class="size-3.5" />
                            Delete
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-7 px-2"
                            @click="clearSelection"
                        >
                            <X class="size-3.5" />
                        </Button>
                    </div>
                </template>
            </div>

            <!-- Table -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Table>
                    <TableHeader>
                        <TableRow
                            v-for="headerGroup in table.getHeaderGroups()"
                            :key="headerGroup.id"
                        >
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                                :class="header.column.id === 'select' ? 'w-10 pl-4' : 'px-2'"
                            >
                                <FlexRender
                                    v-if="!header.isPlaceholder"
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows.length">
                            <TableRow
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                                :data-state="row.getIsSelected() ? 'selected' : undefined"
                                class="cursor-pointer"
                                @click="router.visit(UserController.show(row.original.id).url)"
                            >
                                <TableCell
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                    :class="cell.column.id === 'select' ? 'w-10 pl-4' : 'px-4 py-3'"
                                    @click="cell.column.id === 'select' && $event.stopPropagation()"
                                >
                                    <FlexRender
                                        :render="cell.column.columnDef.cell"
                                        :props="cell.getContext()"
                                    />
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty
                            v-else
                            :colspan="columns.length"
                        >
                            No users found.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <span class="text-xs text-muted-foreground">
                        <template v-if="users.from && users.to">
                            Showing {{ users.from }}–{{ users.to }} of {{ users.total }} users
                        </template>
                        <template v-else>
                            {{ users.total }} users
                        </template>
                    </span>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="users.current_page <= 1"
                            @click="setPage(users.current_page - 1)"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <span class="px-2 text-xs text-muted-foreground">
                            {{ users.current_page }} / {{ users.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="users.current_page >= users.last_page"
                            @click="setPage(users.current_page + 1)"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ selectedCount }} user{{ selectedCount !== 1 ? 's' : '' }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The selected users will be permanently removed.
                        You cannot delete your own account.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showDeleteDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="executeDelete"
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Assign role confirmation dialog -->
        <Dialog v-model:open="showAssignRoleDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Assign "{{ pendingRole?.label }}" to {{ selectedCount }} user{{ selectedCount !== 1 ? 's' : '' }}?</DialogTitle>
                    <DialogDescription>
                        The "{{ pendingRole?.label }}" role will be added to the selected users. Existing roles are not removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showAssignRoleDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button @click="executeAssignRole">
                        Assign role
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
