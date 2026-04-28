<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Tags } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('navigation.admin'), href: '/admin/policies' },
    { title: t('policies.admin.index.title'), href: '/admin/policies' },
];

const typesDialogOpen = ref(false);
</script>

<template>
    <Head :title="$t('policies.admin.index.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Heading
                    :title="$t('policies.admin.index.title')"
                    :description="$t('policies.admin.index.description')"
                />
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="typesDialogOpen = true"
                    >
                        <Tags class="size-4" />
                        {{ $t('policies.admin.index.manage_types') }}
                    </Button>
                    <Link href="/admin/policies/create">
                        <Button>
                            <Plus class="size-4" />
                            {{ $t('policies.admin.index.new_policy') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                {{ $t('policies.admin.index.col_name') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.index.col_type') }}
                            </TableHead>
                            <TableHead>
                                {{
                                    $t(
                                        'policies.admin.index.col_current_version',
                                    )
                                }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.index.col_required') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.index.col_status') }}
                            </TableHead>
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
                                <span v-else class="text-muted-foreground">
                                    {{
                                        $t(
                                            'policies.admin.index.no_published_version',
                                        )
                                    }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    v-if="policy.is_required_for_registration"
                                >
                                    {{
                                        $t(
                                            'policies.admin.index.required_badge',
                                        )
                                    }}
                                </Badge>
                                <span v-else class="text-muted-foreground">
                                    {{ $t('policies.admin.index.optional') }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    v-if="policy.archived_at"
                                    variant="secondary"
                                >
                                    {{ $t('policies.admin.index.archived') }}
                                </Badge>
                                <Badge v-else variant="default">
                                    {{ $t('policies.admin.index.active') }}
                                </Badge>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="policies.length === 0">
                            <TableCell
                                :colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                {{ $t('policies.admin.index.no_policies') }}
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
                    {{ $t('policies.admin.index.no_types_yet_lead') }}
                </p>
                <Button
                    type="button"
                    variant="outline"
                    class="mt-3"
                    @click="typesDialogOpen = true"
                >
                    <Tags class="size-4" />
                    {{ $t('policies.admin.index.add_first_type') }}
                </Button>
            </div>

            <div v-else class="rounded-md border bg-muted/40 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium">
                        {{ $t('policies.admin.index.policy_types_heading') }}
                    </h3>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="typesDialogOpen = true"
                    >
                        {{ $t('policies.admin.index.edit_types') }}
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
