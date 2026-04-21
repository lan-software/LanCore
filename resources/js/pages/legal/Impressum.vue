<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppFooter from '@/components/AppFooter.vue';
import PublicTopbar from '@/components/PublicTopbar.vue';

defineProps<{
    content: string | null;
    organization: {
        name: string | null;
        address_line1: string | null;
        address_line2: string | null;
        email: string | null;
        phone: string | null;
        website: string | null;
        tax_id: string | null;
        registration_id: string | null;
        responsible: string | null;
    };
}>();
</script>

<template>
    <Head :title="$t('legal.impressum.title')" />

    <div class="min-h-screen bg-background">
        <PublicTopbar />

        <main class="mx-auto max-w-3xl px-6 py-12">
            <h1 class="mb-6 text-3xl font-semibold tracking-tight">
                {{ $t('legal.impressum.title') }}
            </h1>

            <div class="space-y-6 text-sm">
                <section v-if="organization.name">
                    <h2 class="mb-2 text-base font-semibold">
                        {{ $t('legal.impressum.provider') }}
                    </h2>
                    <p>{{ organization.name }}</p>
                    <p v-if="organization.address_line1">
                        {{ organization.address_line1 }}
                    </p>
                    <p v-if="organization.address_line2">
                        {{ organization.address_line2 }}
                    </p>
                </section>

                <section
                    v-if="
                        organization.email ||
                        organization.phone ||
                        organization.website
                    "
                >
                    <h2 class="mb-2 text-base font-semibold">
                        {{ $t('legal.impressum.contact') }}
                    </h2>
                    <p v-if="organization.email">
                        {{ $t('legal.impressum.email') }}:
                        <a
                            :href="`mailto:${organization.email}`"
                            class="underline hover:text-foreground"
                            >{{ organization.email }}</a
                        >
                    </p>
                    <p v-if="organization.phone">
                        {{ $t('legal.impressum.phone') }}:
                        {{ organization.phone }}
                    </p>
                    <p v-if="organization.website">
                        {{ $t('legal.impressum.website') }}:
                        <a
                            :href="organization.website"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="underline hover:text-foreground"
                            >{{ organization.website }}</a
                        >
                    </p>
                </section>

                <section v-if="organization.registration_id">
                    <h2 class="mb-2 text-base font-semibold">
                        {{ $t('legal.impressum.registration') }}
                    </h2>
                    <p>{{ organization.registration_id }}</p>
                </section>

                <section v-if="organization.tax_id">
                    <h2 class="mb-2 text-base font-semibold">
                        {{ $t('legal.impressum.taxId') }}
                    </h2>
                    <p>{{ organization.tax_id }}</p>
                </section>

                <section v-if="organization.responsible">
                    <h2 class="mb-2 text-base font-semibold">
                        {{ $t('legal.impressum.responsible') }}
                    </h2>
                    <p class="whitespace-pre-line">
                        {{ organization.responsible }}
                    </p>
                </section>

                <section v-if="content">
                    <h2 class="mb-2 text-base font-semibold">
                        {{ $t('legal.impressum.additional') }}
                    </h2>
                    <div class="whitespace-pre-line">{{ content }}</div>
                </section>

                <p
                    v-if="!content && !organization.name"
                    class="text-muted-foreground"
                >
                    {{ $t('legal.impressum.empty') }}
                </p>
            </div>
        </main>

        <AppFooter />
    </div>
</template>
