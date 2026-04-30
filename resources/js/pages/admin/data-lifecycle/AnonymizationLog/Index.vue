<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as anonymizationLogIndex } from '@/routes/admin/data-lifecycle/anonymization-log';

type LogEntry = {
    id: number;
    user_id: number;
    data_class: string;
    anonymizer_class: string;
    records_scrubbed_count: number;
    records_kept_under_retention_count: number;
    retention_until: string | null;
    completed_at: string;
    summary: Record<string, unknown> | null;
    user: {
        id: number;
        name: string | null;
        email: string | null;
        deleted_at: string | null;
    } | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    entries: Paginated<LogEntry>;
    filters: { user_id?: number | string; data_class?: string };
}>();

const filterState = reactive({
    user_id: props.filters.user_id ?? '',
    data_class: props.filters.data_class ?? '',
});

const applyFilters = () => {
    router.get(
        anonymizationLogIndex().url,
        {
            user_id: filterState.user_id || undefined,
            data_class: filterState.data_class || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const resetFilters = () => {
    filterState.user_id = '';
    filterState.data_class = '';
    router.get(
        anonymizationLogIndex().url,
        {},
        { preserveScroll: true, preserveState: true },
    );
};

const formatDateTime = (s: string | null): string =>
    s ? new Date(s).toLocaleString() : '—';
</script>

<template>
    <AppLayout>
        <Head title="Anonymization log" />

        <div class="space-y-6 p-6">
            <Heading
                title="Anonymization log"
                description="Append-only paper trail of every per-domain PII scrub. Used for compliance audits."
            />

            <form
                class="grid gap-3 rounded border bg-muted/30 p-4 md:grid-cols-3"
                @submit.prevent="applyFilters"
            >
                <div class="grid gap-1">
                    <Label for="user_id">User ID</Label>
                    <Input
                        id="user_id"
                        v-model="filterState.user_id"
                        type="number"
                        min="1"
                        placeholder="e.g. 42"
                    />
                </div>
                <div class="grid gap-1">
                    <Label for="data_class">Data class</Label>
                    <Input
                        id="data_class"
                        v-model="filterState.data_class"
                        type="text"
                        placeholder="e.g. shop.order"
                    />
                </div>
                <div class="flex items-end gap-2">
                    <Button type="submit">Filter</Button>
                    <Button type="button" variant="ghost" @click="resetFilters"
                        >Reset</Button
                    >
                </div>
            </form>

            <div class="overflow-x-auto rounded border">
                <table class="w-full border-collapse text-sm">
                    <thead class="bg-muted/40">
                        <tr class="border-b text-left">
                            <th class="p-2">#</th>
                            <th class="p-2">User</th>
                            <th class="p-2">Data class</th>
                            <th class="p-2">Anonymizer</th>
                            <th class="p-2 text-right">Scrubbed</th>
                            <th class="p-2 text-right">Kept (retention)</th>
                            <th class="p-2">Retention until</th>
                            <th class="p-2">Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="e in entries.data"
                            :key="e.id"
                            class="border-b align-top hover:bg-muted/30"
                        >
                            <td class="p-2 font-mono text-xs">{{ e.id }}</td>
                            <td class="p-2 text-xs">
                                <span v-if="e.user">
                                    {{ e.user.name || '(anonymized)' }}
                                    <span class="text-muted-foreground"
                                        >&lt;{{ e.user.email }}&gt;</span
                                    >
                                </span>
                                <span v-else class="text-muted-foreground">
                                    user #{{ e.user_id }}
                                </span>
                            </td>
                            <td class="p-2 font-mono text-xs">
                                {{ e.data_class }}
                            </td>
                            <td
                                class="p-2 font-mono text-xs text-muted-foreground"
                            >
                                {{
                                    e.anonymizer_class.split('\\').pop() ||
                                    e.anonymizer_class
                                }}
                            </td>
                            <td class="p-2 text-right font-mono">
                                {{ e.records_scrubbed_count }}
                            </td>
                            <td class="p-2 text-right font-mono">
                                {{ e.records_kept_under_retention_count }}
                            </td>
                            <td class="p-2 text-xs">
                                {{ e.retention_until ?? '—' }}
                            </td>
                            <td class="p-2 text-xs">
                                {{ formatDateTime(e.completed_at) }}
                            </td>
                        </tr>
                        <tr v-if="entries.data.length === 0">
                            <td
                                colspan="8"
                                class="p-6 text-center text-muted-foreground"
                            >
                                No anonymization entries match these filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="entries.last_page > 1"
                class="flex flex-wrap items-center gap-1"
            >
                <template v-for="(link, idx) in entries.links" :key="idx">
                    <a
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border px-2 py-1 text-xs hover:bg-muted"
                        :class="{
                            'bg-primary text-primary-foreground': link.active,
                        }"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded border px-2 py-1 text-xs text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
