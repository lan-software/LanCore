<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ChevronDown,
    DoorOpen,
    LifeBuoy,
    Megaphone,
    Plus,
    Swords,
} from 'lucide-vue-next';
import {
    edit,
    create as integrationCreate,
    createLanBrackets,
    createLanShout,
    createLanHelp,
    createLanEntrance,
} from '@/actions/App/Domain/Integration/Http/Controllers/IntegrationAppController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import { index as integrationsRoute } from '@/routes/integrations';
import type { BreadcrumbItem } from '@/types';

export type IntegrationApp = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    callback_url: string | null;
    allowed_scopes: string[] | null;
    is_active: boolean;
    tokens_count: number;
    active_tokens_count: number;
    created_at: string;
    updated_at: string;
};

const props = defineProps<{
    integrationApps: {
        data: IntegrationApp[];
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
    configManagedSlugs?: string[];
}>();

const configManagedSlugs = computed(() => props.configManagedSlugs ?? []);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: integrationsRoute().url },
    { title: 'Integrations', href: integrationsRoute().url },
];

const { setSearch, setPerPage } = useDataTable(
    () => integrationsRoute().url,
    {},
);
</script>

<template>
    <Head title="Integrations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Heading
                    title="Integrations"
                    description="Manage first-party integration apps and API tokens"
                />
                <div class="flex">
                    <Button as-child class="rounded-r-none">
                        <Link :href="integrationCreate().url">
                            <Plus class="size-4" />
                            Add Integration
                        </Link>
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="default"
                                class="rounded-l-none border-l border-primary-foreground/20 px-2"
                            >
                                <ChevronDown class="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem as-child>
                                <Link :href="createLanBrackets().url">
                                    <Swords class="mr-2 size-4" />
                                    Add LanBrackets
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link :href="createLanShout().url">
                                    <Megaphone class="mr-2 size-4" />
                                    Add LanShout
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link :href="createLanHelp().url">
                                    <LifeBuoy class="mr-2 size-4" />
                                    Add LanHelp
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link :href="createLanEntrance().url">
                                    <DoorOpen class="mr-2 size-4" />
                                    Add LanEntrance
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-4">
                <Input
                    placeholder="Search integrations…"
                    class="max-w-sm"
                    :model-value="filters.search ?? ''"
                    @update:model-value="setSearch"
                />
                <div class="ml-auto flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Per page</span>
                    <Select
                        :model-value="String(filters.per_page ?? 20)"
                        @update:model-value="
                            (val: string) => setPerPage(Number(val))
                        "
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
                            <TableHead>Name</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Scopes</TableHead>
                            <TableHead>Tokens</TableHead>
                            <TableHead>Created</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="app in integrationApps.data"
                            :key="app.id"
                            class="cursor-pointer"
                            @click="router.visit(edit(app.id).url)"
                        >
                            <TableCell class="font-medium">{{
                                app.name
                            }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <code
                                        class="rounded bg-muted px-1.5 py-0.5 text-xs"
                                        >{{ app.slug }}</code
                                    >
                                    <Badge
                                        v-if="
                                            configManagedSlugs.includes(
                                                app.slug,
                                            )
                                        "
                                        variant="outline"
                                        class="border-amber-300 bg-amber-50 text-xs text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100"
                                        title="Reconciled from config/integrations.php on every release"
                                    >
                                        config-managed
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    :variant="
                                        app.is_active ? 'default' : 'secondary'
                                    "
                                >
                                    {{ app.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="scope in app.allowed_scopes ??
                                        []"
                                        :key="scope"
                                        variant="outline"
                                        class="text-xs"
                                    >
                                        {{ scope }}
                                    </Badge>
                                    <span
                                        v-if="!app.allowed_scopes?.length"
                                        class="text-sm text-muted-foreground"
                                        >None</span
                                    >
                                </div>
                            </TableCell>
                            <TableCell>
                                {{ app.active_tokens_count }} /
                                {{ app.tokens_count }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{
                                    new Date(
                                        app.created_at,
                                    ).toLocaleDateString()
                                }}
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="integrationApps.data.length === 0">
                            <TableCell
                                :colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No integrations found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div
                v-if="integrationApps.last_page > 1"
                class="flex items-center justify-between"
            >
                <p class="text-sm text-muted-foreground">
                    Showing {{ integrationApps.data.length }} of
                    {{ integrationApps.total }} integrations
                </p>
                <div class="flex gap-1">
                    <template
                        v-for="link in integrationApps.links"
                        :key="link.label"
                    >
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
