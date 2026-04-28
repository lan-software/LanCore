<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
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

interface AuditRow {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: number;
    actor: { id: number; name: string } | null;
    old_values: Record<string, unknown> | string | null;
    new_values: Record<string, unknown> | string | null;
    created_at: string;
}

interface DiffRow {
    from_version: number;
    to_version: number;
    locale: string;
    html: string;
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
    audits: AuditRow[];
    diffs: DiffRow[];
}>();

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('policies.admin.index.title'), href: '/admin/policies' },
    { title: props.policy.name, href: `/admin/policies/${props.policy.id}` },
];

function eventLabel(event: string): string {
    const known = ['created', 'updated', 'restored', 'deleted'];

    if (known.includes(event)) {
        return t(`policies.admin.show.audit_event_${event}`);
    }

    return event;
}

function formatChanges(values: AuditRow['new_values']): string {
    if (!values || typeof values === 'string') {
        return '';
    }

    return Object.keys(values).join(', ');
}
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
                        <span>
                            {{ $t('policies.admin.show.key_label') }}:
                            <code>{{ policy.key }}</code>
                        </span>
                        <span v-if="policy.type"
                            >· {{ policy.type.label }}</span
                        >
                        <Badge v-if="policy.is_required_for_registration">
                            {{
                                $t(
                                    'policies.admin.show.required_for_registration_badge',
                                )
                            }}
                        </Badge>
                        <Badge v-if="policy.archived_at" variant="secondary">
                            {{ $t('policies.admin.show.archived_badge') }}
                        </Badge>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/admin/policies/${policy.id}/edit`">
                        <Button variant="outline">
                            <Pencil class="size-4" />
                            {{ $t('policies.admin.show.edit_metadata') }}
                        </Button>
                    </Link>
                    <Link
                        :href="`/admin/policies/${policy.id}/versions/create`"
                    >
                        <Button>
                            <Plus class="size-4" />
                            {{ $t('policies.admin.show.publish_new_version') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                {{ $t('policies.admin.show.col_version') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_locale') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_type') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_published') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_by') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_pdf') }}
                            </TableHead>
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
                                    {{
                                        $t(
                                            'policies.admin.show.requires_acceptance_badge',
                                        )
                                    }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ version.locale }}</TableCell>
                            <TableCell>
                                <Badge
                                    v-if="version.is_non_editorial_change"
                                    variant="destructive"
                                >
                                    {{
                                        $t(
                                            'policies.admin.show.non_editorial_badge',
                                        )
                                    }}
                                </Badge>
                                <Badge v-else variant="secondary">
                                    {{
                                        $t(
                                            'policies.admin.show.editorial_badge',
                                        )
                                    }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                {{
                                    new Date(
                                        version.published_at,
                                    ).toLocaleDateString()
                                }}
                            </TableCell>
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
                                <span v-else class="text-muted-foreground">
                                    —
                                </span>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="policy.versions.length === 0">
                            <TableCell
                                :colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                {{ $t('policies.admin.show.no_versions_yet') }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <section v-if="props.diffs.length > 0" class="space-y-4">
                <div
                    v-for="diff in props.diffs"
                    :key="`${diff.locale}-${diff.from_version}-${diff.to_version}`"
                    class="rounded-md border"
                >
                    <div
                        class="flex items-center justify-between border-b bg-muted/40 px-4 py-2"
                    >
                        <h3 class="text-sm font-semibold">
                            {{
                                $t('policies.admin.show.diff_heading', {
                                    from: diff.from_version,
                                    to: diff.to_version,
                                })
                            }}
                        </h3>
                        <span class="text-xs text-muted-foreground">
                            {{ diff.locale }}
                        </span>
                    </div>
                    <div
                        v-if="diff.html.trim()"
                        class="max-h-96 overflow-auto p-2 font-mono text-xs"
                        v-html="diff.html"
                    />
                    <p v-else class="p-4 text-sm text-muted-foreground italic">
                        {{ $t('policies.admin.show.diff_empty') }}
                    </p>
                </div>
            </section>

            <section class="rounded-md border">
                <h3
                    class="border-b bg-muted/40 px-4 py-2 text-sm font-semibold"
                >
                    {{ $t('policies.admin.show.audit_log_heading') }}
                </h3>
                <ul v-if="props.audits.length > 0" class="divide-y text-sm">
                    <li
                        v-for="audit in props.audits"
                        :key="audit.id"
                        class="px-4 py-2"
                    >
                        <div class="flex flex-wrap items-baseline gap-2">
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{
                                    new Date(audit.created_at).toLocaleString()
                                }}
                            </span>
                            <span class="font-medium">
                                {{
                                    audit.actor?.name ??
                                    $t(
                                        'policies.admin.show.audit_actor_unknown',
                                    )
                                }}
                            </span>
                            <span class="text-muted-foreground">
                                {{ eventLabel(audit.event) }}
                                {{ audit.auditable_type }}#{{
                                    audit.auditable_id
                                }}
                            </span>
                        </div>
                        <p
                            v-if="formatChanges(audit.new_values)"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ formatChanges(audit.new_values) }}
                        </p>
                    </li>
                </ul>
                <p
                    v-else
                    class="px-4 py-6 text-sm text-muted-foreground italic"
                >
                    {{ $t('policies.admin.show.audit_log_empty') }}
                </p>
            </section>
        </div>
    </AppLayout>
</template>
