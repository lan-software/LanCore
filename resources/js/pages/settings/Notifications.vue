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
    push_on_news: boolean;
    push_on_events: boolean;
    push_on_news_comments: boolean;
    push_on_program_time_slots: boolean;
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
    push_on_news: props.preferences.push_on_news,
    push_on_events: props.preferences.push_on_events,
    push_on_news_comments: props.preferences.push_on_news_comments,
    push_on_program_time_slots: props.preferences.push_on_program_time_slots,
});

function submit() {
    form.patch(update().url);
}

type NotificationRow = {
    label: string;
    description: string;
    mailKey: keyof typeof form;
    pushKey: keyof typeof form;
};

const notificationRows: NotificationRow[] = [
    {
        label: 'News articles',
        description: 'When a new article is published',
        mailKey: 'mail_on_news',
        pushKey: 'push_on_news',
    },
    {
        label: 'Events',
        description: 'When a new event is published',
        mailKey: 'mail_on_events',
        pushKey: 'push_on_events',
    },
    {
        label: 'News comments',
        description: 'When someone comments on a news article',
        mailKey: 'mail_on_news_comments',
        pushKey: 'push_on_news_comments',
    },
    {
        label: 'Program time slots',
        description: 'When a program time slot is about to start',
        mailKey: 'mail_on_program_time_slots',
        pushKey: 'push_on_program_time_slots',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Notification settings" />

        <h1 class="sr-only">Notification settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Notifications"
                    description="Choose how you want to be notified"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="overflow-hidden rounded-lg border">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 text-left font-medium">Notification</th>
                                    <th class="w-20 px-4 py-3 text-center font-medium">Email</th>
                                    <th class="w-20 px-4 py-3 text-center font-medium">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span>Push</span>
                                            <span class="text-[10px] font-normal text-muted-foreground">coming soon</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in notificationRows" :key="row.mailKey" class="border-b last:border-b-0">
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ row.label }}</div>
                                        <p class="text-xs text-muted-foreground">{{ row.description }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center">
                                            <Checkbox
                                                :id="row.mailKey"
                                                v-model:checked="(form[row.mailKey] as boolean)"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center opacity-40">
                                            <Checkbox
                                                :id="row.pushKey"
                                                :checked="false"
                                                disabled
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
