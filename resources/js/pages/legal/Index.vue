<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppFooter from '@/components/AppFooter.vue';
import PublicTopbar from '@/components/PublicTopbar.vue';

interface PolicyEntry {
    key: string;
    name: string;
    description: string | null;
    current_version: {
        version_number: number;
        locale: string;
        published_at: string | null;
    } | null;
}

defineProps<{
    policies: PolicyEntry[];
}>();
</script>

<template>
    <Head :title="$t('legal.index.title')" />

    <div class="min-h-screen bg-background">
        <PublicTopbar />

        <main class="mx-auto max-w-3xl px-6 py-12">
            <h1 class="mb-6 text-3xl font-semibold tracking-tight">
                {{ $t('legal.index.title') }}
            </h1>

            <p class="mb-8 text-sm text-muted-foreground">
                {{ $t('legal.index.lead') }}
            </p>

            <ul v-if="policies.length" class="space-y-3">
                <li
                    v-for="policy in policies"
                    :key="policy.key"
                    class="rounded-md border bg-card p-4"
                >
                    <Link
                        :href="`/policies/${policy.key}`"
                        class="block text-base font-medium hover:underline"
                    >
                        {{ policy.name }}
                    </Link>
                    <p
                        v-if="policy.description"
                        class="mt-1 text-sm text-muted-foreground"
                    >
                        {{ policy.description }}
                    </p>
                    <p
                        v-if="policy.current_version"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        v{{ policy.current_version.version_number }} ({{
                            policy.current_version.locale
                        }})
                    </p>
                </li>
            </ul>

            <p v-else class="text-sm text-muted-foreground">
                {{ $t('legal.index.empty') }}
            </p>
        </main>

        <AppFooter />
    </div>
</template>
