<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AnnouncementDismissals from '@/actions/App/Domain/Announcement/Http/Controllers/AnnouncementDismissalController';

type SharedAnnouncement = {
    id: number;
    audience: string | null;
    severity: string | null;
    title: string;
    body: string | null;
    starts_at: string | null;
    ends_at: string | null;
    dismissible: boolean;
};

const page = usePage<{ announcements: SharedAnnouncement[] | null }>();

const initial = computed<SharedAnnouncement[]>(
    () => page.props.announcements ?? [],
);
const local = ref<SharedAnnouncement[]>([...initial.value]);

watch(initial, (next: SharedAnnouncement[]): void => {
    local.value = [...next];
});

const classesFor = (severity: string | null): string => {
    switch (severity) {
        case 'emergency':
            return 'border-red-300 bg-red-100 text-red-900 dark:border-red-500/40 dark:bg-red-500/15 dark:text-red-100';
        case 'silent':
            return 'border-slate-300 bg-slate-100 text-slate-900 dark:border-slate-500/40 dark:bg-slate-500/15 dark:text-slate-100';
        default:
            return 'border-sky-300 bg-sky-100 text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-100';
    }
};

const dismiss = (announcement: SharedAnnouncement): void => {
    local.value = local.value.filter(
        (a: SharedAnnouncement) => a.id !== announcement.id,
    );

    router.visit(AnnouncementDismissals.store(announcement.id).url, {
        method: 'post',
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <div v-if="local.length > 0" class="flex w-full flex-col gap-1">
        <div
            v-for="announcement in local"
            :key="announcement.id"
            :class="classesFor(announcement.severity)"
            class="flex w-full items-start justify-between gap-3 border-b px-4 py-2 text-sm"
            role="status"
        >
            <div class="flex-1">
                <p class="font-semibold">{{ announcement.title }}</p>
                <p v-if="announcement.body" class="mt-0.5 opacity-90">
                    {{ announcement.body }}
                </p>
            </div>
            <button
                v-if="announcement.dismissible"
                type="button"
                class="shrink-0 rounded px-2 py-1 text-xs font-medium underline-offset-2 hover:underline"
                @click="dismiss(announcement)"
            >
                Dismiss
            </button>
        </div>
    </div>
</template>
