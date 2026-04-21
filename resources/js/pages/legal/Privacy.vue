<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppFooter from '@/components/AppFooter.vue';
import PublicTopbar from '@/components/PublicTopbar.vue';

defineProps<{
    content: string | null;
    organization: {
        name: string | null;
        email: string | null;
    };
}>();
</script>

<template>
    <Head :title="$t('legal.privacy.title')" />

    <div class="min-h-screen bg-background">
        <PublicTopbar />

        <main class="mx-auto max-w-3xl px-6 py-12">
            <h1 class="mb-6 text-3xl font-semibold tracking-tight">
                {{ $t('legal.privacy.title') }}
            </h1>

            <div v-if="content" class="space-y-4 text-sm whitespace-pre-line">
                {{ content }}
            </div>
            <div v-else class="space-y-4 text-sm text-muted-foreground">
                <p>{{ $t('legal.privacy.empty') }}</p>
                <p v-if="organization.email">
                    {{ $t('legal.privacy.contactFallback') }}
                    <a
                        :href="`mailto:${organization.email}`"
                        class="underline hover:text-foreground"
                        >{{ organization.email }}</a
                    >
                </p>
            </div>
        </main>

        <AppFooter />
    </div>
</template>
