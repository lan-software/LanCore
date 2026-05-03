<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { show as userShow } from '@/actions/App/Http/Controllers/Users/UserController';
import AuditTable from '@/components/AuditTable.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as usersIndex } from '@/routes/users';
import { by as auditByRoute, on as auditOnRoute } from '@/routes/users/audits';
import type { BreadcrumbItem } from '@/types';
import type { Audit } from '@/types/domain';

type Perspective = 'on' | 'by';

interface ByAudit extends Audit {
    auditable_type?: string | null;
    auditable_id?: number | null;
}

interface PaginatedAudits {
    data: ByAudit[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    user: { id: number; name: string; email: string };
    perspective: Perspective;
    audits: PaginatedAudits;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: usersIndex().url },
    { title: 'Users', href: usersIndex().url },
    { title: props.user.name, href: userShow(props.user.id).url },
    { title: 'Audit Log', href: '#' },
];

const heading =
    props.perspective === 'on'
        ? `Changes made to ${props.user.name}`
        : `Changes made by ${props.user.name}`;

const onUrl = auditOnRoute(props.user.id).url;
const byUrl = auditByRoute(props.user.id).url;
</script>

<template>
    <Head :title="`Audit Log – ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ heading }}</h2>
                <Button variant="outline" as-child>
                    <Link :href="userShow(user.id).url">Back to User</Link>
                </Button>
            </div>

            <div class="flex gap-2">
                <Button
                    :variant="perspective === 'on' ? 'default' : 'outline'"
                    as-child
                >
                    <Link :href="onUrl">Changes to user</Link>
                </Button>
                <Button
                    :variant="perspective === 'by' ? 'default' : 'outline'"
                    as-child
                >
                    <Link :href="byUrl">Changes by user</Link>
                </Button>
            </div>

            <AuditTable
                :audits="audits"
                :show-actor="perspective === 'on'"
                :empty-label="
                    perspective === 'on'
                        ? 'No changes to this user have been recorded.'
                        : 'This user has not made any audited changes yet.'
                "
            />
        </div>
    </AppLayout>
</template>
