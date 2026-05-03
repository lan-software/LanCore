<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit as newsArticleEdit } from '@/actions/App/Domain/News/Http/Controllers/NewsArticleController';
import AuditTable from '@/components/AuditTable.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as newsRoute } from '@/routes/news';
import type { BreadcrumbItem } from '@/types';
import type { Audit } from '@/types/domain';

interface PaginatedAudits {
    data: Audit[];
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
    article: { id: number; title: string };
    audits: PaginatedAudits;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: newsRoute().url },
    { title: 'News', href: newsRoute().url },
    { title: props.article.title, href: newsArticleEdit(props.article.id).url },
    { title: 'Audit Log', href: '#' },
];
</script>

<template>
    <Head :title="`Audit Log – ${article.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">
                    Audit Log for {{ article.title }}
                </h2>
                <Button variant="outline" as-child>
                    <Link :href="newsArticleEdit(article.id).url"
                        >Back to Article</Link
                    >
                </Button>
            </div>

            <AuditTable :audits="audits" />
        </div>
    </AppLayout>
</template>
