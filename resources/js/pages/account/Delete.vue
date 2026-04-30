<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

type PendingRequest = {
    id: number;
    status: string;
    scheduled_for: string | null;
    email_confirmed_at: string | null;
};

defineProps<{
    pendingRequest: PendingRequest | null;
    flash?: { level: 'error' | 'info'; message: string } | null;
}>();

const form = useForm({
    password: '',
    reason: '',
});

const submit = () => {
    form.post('/account/delete', {
        preserveScroll: true,
        onSuccess: () => form.reset('password', 'reason'),
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Delete account" />

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Delete account"
                    description="Permanently anonymize your personal data after a 30-day grace period."
                />

                <div
                    v-if="flash"
                    class="rounded border border-red-300 bg-red-50 p-4 text-sm text-red-800"
                >
                    {{ flash.message }}
                </div>

                <div
                    v-if="pendingRequest"
                    class="rounded border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-900"
                >
                    <p class="font-semibold">Deletion request pending.</p>
                    <p v-if="pendingRequest.status === 'pending_email_confirm'">
                        Click the confirmation link in your email to start the
                        30-day grace period.
                    </p>
                    <p v-else-if="pendingRequest.scheduled_for">
                        Scheduled for {{ pendingRequest.scheduled_for }}.
                    </p>
                </div>

                <form
                    v-else
                    class="space-y-4"
                    @submit.prevent="submit"
                >
                    <p class="text-sm text-muted-foreground">
                        Confirm your password to schedule deletion. We'll send a
                        confirmation email; clicking the link in that email
                        starts a 30-day grace period during which you can still
                        cancel.
                    </p>

                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            name="password"
                            autocomplete="current-password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="reason"
                            >Reason (optional, helps us improve)</Label
                        >
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            class="min-h-24 rounded border border-neutral-300 p-2 text-sm"
                        />
                        <InputError :message="form.errors.reason" />
                    </div>

                    <Button
                        variant="destructive"
                        type="submit"
                        :disabled="form.processing"
                    >
                        Request account deletion
                    </Button>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
