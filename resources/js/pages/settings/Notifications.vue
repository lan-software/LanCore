<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { update } from '@/actions/App/Domain/Notification/Http/Controllers/NotificationSettingsController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/notifications';
import type { BreadcrumbItem } from '@/types';

type NotificationPreferences = {
    mail_on_news: boolean;
    mail_on_events: boolean;
    mail_on_news_comments: boolean;
    mail_on_program_time_slots: boolean;
};

const props = defineProps<{
    preferences: NotificationPreferences;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notification settings',
        href: edit(),
    },
];

const form = useForm({
    mail_on_news: props.preferences.mail_on_news,
    mail_on_events: props.preferences.mail_on_events,
    mail_on_news_comments: props.preferences.mail_on_news_comments,
    mail_on_program_time_slots: props.preferences.mail_on_program_time_slots,
});

function submit() {
    form.patch(update().url);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Notification settings" />

        <h1 class="sr-only">Notification settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Email notifications"
                    description="Choose which email notifications you want to receive"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <Checkbox id="mail_on_news" v-model:checked="form.mail_on_news" />
                            <Label for="mail_on_news" class="text-sm font-normal">News articles</Label>
                        </div>
                        <p class="text-xs text-muted-foreground ml-6">Receive an email when a new article is published</p>

                        <div class="flex items-center gap-2">
                            <Checkbox id="mail_on_events" v-model:checked="form.mail_on_events" />
                            <Label for="mail_on_events" class="text-sm font-normal">Events</Label>
                        </div>
                        <p class="text-xs text-muted-foreground ml-6">Receive an email when a new event is published</p>

                        <div class="flex items-center gap-2">
                            <Checkbox id="mail_on_news_comments" v-model:checked="form.mail_on_news_comments" />
                            <Label for="mail_on_news_comments" class="text-sm font-normal">News comments</Label>
                        </div>
                        <p class="text-xs text-muted-foreground ml-6">Receive an email when someone comments on a news article</p>

                        <div class="flex items-center gap-2">
                            <Checkbox id="mail_on_program_time_slots" v-model:checked="form.mail_on_program_time_slots" />
                            <Label for="mail_on_program_time_slots" class="text-sm font-normal">Program time slots</Label>
                        </div>
                        <p class="text-xs text-muted-foreground ml-6">Receive an email when a program time slot is about to start (requires a ticket for the event)</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save preferences' }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
