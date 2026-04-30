<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

type DeletionRequest = {
    id: number;
    status: string;
    scheduled_for: string | null;
};

const props = defineProps<{
    deletionRequest?: DeletionRequest | null;
    message?: string;
}>();

const cancelForm = useForm({});

const cancel = () => {
    if (!props.deletionRequest) {
return;
}

    cancelForm.delete(`/account/delete/${props.deletionRequest.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Account deletion pending" />

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Deletion in progress"
                    description="Your account is in a 30-day grace period. You can still cancel."
                />

                <div
                    v-if="message"
                    class="rounded border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-900"
                >
                    {{ message }}
                </div>

                <div
                    v-if="deletionRequest && deletionRequest.scheduled_for"
                    class="rounded border border-orange-300 bg-orange-50 p-4 text-sm text-orange-900"
                >
                    Scheduled for permanent anonymization on
                    <strong>{{ deletionRequest.scheduled_for }}</strong>.
                </div>

                <div class="flex flex-col items-start gap-2">
                    <p class="text-sm text-muted-foreground">
                        While the request is pending you remain logged in but
                        the account is read-only. You can still download a
                        GDPR Article 15 export of your data.
                    </p>

                    <Button
                        v-if="deletionRequest"
                        variant="default"
                        :disabled="cancelForm.processing"
                        @click="cancel"
                    >
                        Cancel deletion
                    </Button>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
