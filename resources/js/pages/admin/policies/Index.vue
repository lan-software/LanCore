<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Tags } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import PolicyTypeManagerDialog from '@/components/policies/PolicyTypeManagerDialog.vue';
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

interface PolicyTypeRow {
    id: number;
    key: string;
    label: string;
    description: string | null;
}

interface PolicyRow {
    id: number;
    key: string;
    name: string;
    description: string | null;
    is_required_for_registration: boolean;
    sort_order: number;
    archived_at: string | null;
    type: PolicyTypeRow | null;
    current_version: {
        id: number;
        version_number: number;
        locale: string;
    } | null;
}

const props = defineProps<{
    policies: PolicyRow[];
    policyTypes: PolicyTypeRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin/policies' },
    { title: 'Policies', href: '/admin/policies' },
];

const typesDialogOpen = ref(false);
</script>

<template>
    <Head title="Policies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Heading
                    title="Policies"
                    description="Manage the platform's terms, privacy, and other consent policies"
                />
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="typesDialogOpen = true"
                    >
                        <Tags class="size-4" />
                        Manage types
                    </Button>
                    <Link href="/admin/policies/create">
                        <Button>
                            <Plus class="size-4" />
                            New policy
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Current version</TableHead>
                            <TableHead>Required at registration</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="policy in policies"
                            :key="policy.id"
                            class="cursor-pointer"
                            @click="
                                router.visit(`/admin/policies/${policy.id}`)
                            "
                        >
                            <TableCell>
                                <div class="font-medium">{{ policy.name }}</div>
                                <div
                                    v-if="policy.description"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ policy.description }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <span v-if="policy.type">{{
                                    policy.type.label
                                }}</span>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                            <TableCell>
                                <span v-if="policy.current_version">
                                    v{{ policy.current_version.version_number }}
                                    <span class="text-xs text-muted-foreground">
                                        ({{ policy.current_version.locale }})
                                    </span>
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >No published version</span
                                >
                            </TableCell>
                            <TableCell>
                                <Badge
                                    v-if="policy.is_required_for_registration"
                                    >Required</Badge
                                >
                                <span v-else class="text-muted-foreground"
                                    >Optional</span
                                >
                            </TableCell>
                            <TableCell>
                                <Badge
                                    v-if="policy.archived_at"
                                    variant="secondary"
                                    >Archived</Badge
                                >
                                <Badge v-else variant="default">Active</Badge>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="policies.length === 0">
                            <TableCell
                                :colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No policies yet. Create one to get started.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div
                v-if="policyTypes.length === 0"
                class="rounded-md border border-dashed bg-muted/40 p-6 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    No policy types yet. You need at least one type before you
                    can create a policy.
                </p>
                <Button
                    type="button"
                    variant="outline"
                    class="mt-3"
                    @click="typesDialogOpen = true"
                >
                    <Tags class="size-4" />
                    Add the first type
                </Button>
            </div>

            <div v-else class="rounded-md border bg-muted/40 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium">Policy types</h3>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="typesDialogOpen = true"
                    >
                        Edit
                    </Button>
                </div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <Badge
                        v-for="type in policyTypes"
                        :key="type.id"
                        variant="outline"
                    >
                        {{ type.label }}
                    </Badge>
                </div>
            </div>
        </div>

        <PolicyTypeManagerDialog
            v-model:open="typesDialogOpen"
            :policy-types="props.policyTypes"
        />
    </AppLayout>
</template>
