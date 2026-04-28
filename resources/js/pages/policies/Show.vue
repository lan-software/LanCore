<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppFooter from '@/components/AppFooter.vue';
import PublicTopbar from '@/components/PublicTopbar.vue';

defineProps<{
    policy: {
        key: string;
        name: string;
        description: string | null;
        type: { key: string; label: string } | null;
    };
    version: {
        version_number: number;
        locale: string;
        content: string;
        public_statement: string | null;
        effective_at: string | null;
        published_at: string | null;
    };
}>();
</script>

<template>
    <Head :title="policy.name" />

    <div class="min-h-screen bg-background">
        <PublicTopbar />

        <main class="mx-auto max-w-3xl px-6 py-12">
            <Link
                href="/legal"
                class="text-sm text-muted-foreground hover:text-foreground"
            >
                &larr; {{ $t('legal.index.title') }}
            </Link>

            <h1 class="mt-4 mb-2 text-3xl font-semibold tracking-tight">
                {{ policy.name }}
            </h1>

            <p class="mb-6 text-xs text-muted-foreground">
                v{{ version.version_number }} ({{ version.locale }})
                <span v-if="version.effective_at">
                    · {{ new Date(version.effective_at).toLocaleDateString() }}
                </span>
            </p>

            <div
                v-if="version.public_statement"
                class="mb-6 rounded border-l-4 border-blue-400 bg-blue-50 p-4 text-sm dark:bg-blue-950/30"
            >
                <strong>{{ $t('policies.show.from_operator') }}:</strong>
                {{ version.public_statement }}
            </div>

            <pre class="rounded bg-muted p-4 text-sm whitespace-pre-wrap">{{
                version.content
            }}</pre>
        </main>

        <AppFooter />
    </div>
</template>
