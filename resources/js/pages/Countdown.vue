<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Ticket } from 'lucide-vue-next';
import { computed } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import BannerCarousel from '@/components/BannerCarousel.vue';
import CountdownTimer from '@/components/countdown/CountdownTimer.vue';
import NewsletterSignupForm from '@/components/countdown/NewsletterSignupForm.vue';
import PublicTopbar from '@/components/PublicTopbar.vue';
import { Button } from '@/components/ui/button';
import { index as shopIndex } from '@/routes/shop';

const props = defineProps<{
    event: {
        id: string;
        name: string;
        start_date: string | null;
        banner_image_urls: string[];
    } | null;
    newsletter: {
        list_id: string;
        list_name: string;
        list_description: string | null;
    } | null;
}>();

const hasUpcomingEvent = computed(
    () => props.event !== null && props.event.start_date !== null,
);
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <Head title="Countdown" />

        <PublicTopbar />

        <main class="container mx-auto flex-1 px-4 py-10 sm:py-16">
            <section class="mx-auto max-w-3xl space-y-10">
                <header class="space-y-3 text-center">
                    <p
                        class="text-sm tracking-wider text-muted-foreground uppercase"
                    >
                        Next event
                    </p>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        {{ event ? event.name : 'No upcoming event scheduled' }}
                    </h1>
                </header>

                <BannerCarousel
                    v-if="event && event.banner_image_urls.length > 0"
                    :images="event.banner_image_urls"
                />

                <CountdownTimer
                    v-if="hasUpcomingEvent && event && event.start_date"
                    :target-iso="event.start_date"
                />

                <div v-if="hasUpcomingEvent" class="flex justify-center">
                    <Button as-child size="lg" class="gap-2">
                        <Link :href="shopIndex().url">
                            <Ticket class="size-5" />
                            Get your ticket
                        </Link>
                    </Button>
                </div>

                <NewsletterSignupForm
                    v-if="newsletter"
                    :list-name="newsletter.list_name"
                    :list-description="newsletter.list_description"
                />

                <p
                    v-else
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    Newsletter signup is not currently configured.
                </p>
            </section>
        </main>

        <AppFooter />
    </div>
</template>
