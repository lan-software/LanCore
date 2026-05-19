<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { update } from '@/actions/App/Domain/Newsletter/Http/Controllers/User/EmailSettingsController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit as editEmailSettings } from '@/routes/email-settings';
import type { BreadcrumbItem } from '@/types';

type EmailListRow = {
    id: string;
    name: string;
    description: string | null;
    tags: string[];
    status: 'enabled' | 'unsubscribed' | 'blocklisted';
    subscribed_at: string | null;
    last_synced_at: string | null;
};

const props = defineProps<{
    lists: EmailListRow[];
    listmonkEnabled: boolean;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'E-Mail settings', href: editEmailSettings() },
];

const initialIds = computed<number[]>(() =>
    props.lists.filter((l) => l.status === 'enabled').map((l) => l.id),
);

const form = useForm<{ subscribed_list_ids: number[] }>({
    subscribed_list_ids: [...initialIds.value],
});

function toggle(listId: string, enabled: boolean) {
    if (enabled) {
        if (!form.subscribed_list_ids.includes(listId)) {
            form.subscribed_list_ids.push(listId);
        }
    } else {
        form.subscribed_list_ids = form.subscribed_list_ids.filter(
            (id) => id !== listId,
        );
    }
}

function isSubscribed(listId: string): boolean {
    return form.subscribed_list_ids.includes(listId);
}

function submit() {
    form.patch(update().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults({
                subscribed_list_ids: [...form.subscribed_list_ids],
            });
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="E-Mail settings" />

        <h1 class="sr-only">E-Mail settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="E-Mail Settings"
                    description="Choose which curated newsletter lists you want to receive."
                />

                <p
                    v-if="!listmonkEnabled"
                    class="rounded-lg border border-dashed p-3 text-xs text-muted-foreground"
                >
                    Listmonk is not currently enabled. Your selections are saved
                    locally and will sync once the platform admin activates the
                    integration.
                </p>

                <form
                    v-if="props.lists.length"
                    @submit.prevent="submit"
                    class="space-y-6"
                >
                    <div class="overflow-hidden rounded-lg border">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 text-left font-medium">
                                        List
                                    </th>
                                    <th
                                        class="w-24 px-4 py-3 text-center font-medium"
                                    >
                                        Subscribed
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="list in props.lists"
                                    :key="list.id"
                                    class="border-b last:border-b-0"
                                >
                                    <td class="px-4 py-3">
                                        <div class="font-medium">
                                            {{ list.name }}
                                        </div>
                                        <p
                                            v-if="list.description"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ list.description }}
                                        </p>
                                        <p
                                            v-if="list.status === 'blocklisted'"
                                            class="text-xs text-destructive"
                                        >
                                            This address is blocklisted in
                                            Listmonk; contact an admin to
                                            re-enable.
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center">
                                            <Checkbox
                                                :id="`list-${list.id}`"
                                                :model-value="
                                                    isSubscribed(list.id)
                                                "
                                                :disabled="
                                                    list.status ===
                                                    'blocklisted'
                                                "
                                                @update:model-value="
                                                    (val) =>
                                                        toggle(
                                                            list.id,
                                                            val === true,
                                                        )
                                                "
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing ? 'Saving…' : 'Save preferences'
                            }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-if="form.recentlySuccessful"
                                class="text-sm text-muted-foreground"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </form>

                <p
                    v-else
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    No newsletter lists are currently available for users.
                </p>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
