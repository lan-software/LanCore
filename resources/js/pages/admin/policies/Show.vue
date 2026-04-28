<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface PolicyVersionRow {
    id: number;
    version_number: number;
    locale: string;
    is_non_editorial_change: boolean;
    public_statement: string | null;
    effective_at: string;
    published_at: string;
    pdf_path: string | null;
    published_by: { id: number; name: string } | null;
}

const props = defineProps<{
    policy: {
        id: number;
        key: string;
        name: string;
        description: string | null;
        is_required_for_registration: boolean;
        archived_at: string | null;
        required_acceptance_version_id: number | null;
        type: { label: string } | null;
        versions: PolicyVersionRow[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Policies', href: '/admin/policies' },
    { title: props.policy.name, href: `/admin/policies/${props.policy.id}` },
];
</script>

<template>
    <Head :title="policy.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <Heading
                        :title="policy.name"
                        :description="policy.description ?? ''"
                    />
                    <div
                        class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                    >
                        <span
                            >Key: <code>{{ policy.key }}</code></span
                        >
                        <span v-if="policy.type"
                            >· {{ policy.type.label }}</span
                        >
                        <Badge v-if="policy.is_required_for_registration"
                            >Required at registration</Badge
                        >
                        <Badge v-if="policy.archived_at" variant="secondary"
                            >Archived</Badge
                        >
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/admin/policies/${policy.id}/edit`">
                        <Button variant="outline">
                            <Pencil class="size-4" />
                            Edit metadata
                        </Button>
                    </Link>
                    <Link
                        :href="`/admin/policies/${policy.id}/versions/create`"
                    >
                        <Button>
                            <Plus class="size-4" />
                            Publish new version
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Version</TableHead>
                            <TableHead>Locale</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Published</TableHead>
                            <TableHead>By</TableHead>
                            <TableHead>PDF</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="version in policy.versions"
                            :key="version.id"
                            :class="{
                                'bg-amber-50 dark:bg-amber-950/30':
                                    policy.required_acceptance_version_id ===
                                    version.id,
                            }"
                        >
                            <TableCell class="font-medium">
                                v{{ version.version_number }}
                                <Badge
                                    v-if="
                                        policy.required_acceptance_version_id ===
                                        version.id
                                    "
                                    class="ml-2"
                                >
                                    Requires acceptance
                                </Badge>
                            </TableCell>
                            <TableCell>{{ version.locale }}</TableCell>
                            <TableCell>
                                <Badge
                                    v-if="version.is_non_editorial_change"
                                    variant="destructive"
                                >
                                    Non-editorial
                                </Badge>
                                <Badge v-else variant="secondary"
                                    >Editorial</Badge
                                >
                            </TableCell>
                            <TableCell>{{
                                new Date(
                                    version.published_at,
                                ).toLocaleDateString()
                            }}</TableCell>
                            <TableCell>
                                {{ version.published_by?.name ?? '—' }}
                            </TableCell>
                            <TableCell>
                                <span
                                    v-if="version.pdf_path"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ version.pdf_path }}
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="policy.versions.length === 0">
                            <TableCell
                                :colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No versions yet. Publish the first one to make
                                this policy effective.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
