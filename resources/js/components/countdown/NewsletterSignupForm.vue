<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { store as publicSubscribe } from '@/actions/App/Domain/Newsletter/Http/Controllers/Public/NewsletterSubscribeController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    listName: string;
    listDescription: string | null;
}>();

const page = usePage<{
    auth?: { user?: { email?: string; name?: string } | null };
    flash?: { status?: string | null };
}>();

const initialEmail = computed<string>(() => page.props.auth?.user?.email ?? '');
const initialName = computed<string>(() => page.props.auth?.user?.name ?? '');

const form = useForm({
    email: initialEmail.value,
    name: initialName.value,
});

function submit() {
    form.post(publicSubscribe().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('email', 'name');
        },
    });
}
</script>

<template>
    <form
        @submit.prevent="submit"
        class="flex flex-col gap-3 rounded-lg border bg-card p-6 shadow-sm"
    >
        <div>
            <h3 class="text-lg font-semibold">Stay in the loop</h3>
            <p class="text-sm text-muted-foreground">
                Subscribe to <strong>{{ listName }}</strong> to get reminders
                and announcements.
            </p>
            <p
                v-if="listDescription"
                class="mt-1 text-xs text-muted-foreground"
            >
                {{ listDescription }}
            </p>
        </div>

        <div class="flex flex-col gap-2">
            <Label for="newsletter-email">Email</Label>
            <Input
                id="newsletter-email"
                v-model="form.email"
                type="email"
                required
                placeholder="you@example.com"
                :disabled="form.processing"
            />
            <p v-if="form.errors.email" class="text-xs text-destructive">
                {{ form.errors.email }}
            </p>
        </div>

        <div class="flex flex-col gap-2">
            <Label for="newsletter-name">Name (optional)</Label>
            <Input
                id="newsletter-name"
                v-model="form.name"
                type="text"
                placeholder="Your display name"
                :disabled="form.processing"
            />
            <p v-if="form.errors.name" class="text-xs text-destructive">
                {{ form.errors.name }}
            </p>
        </div>

        <Button type="submit" :disabled="form.processing" class="w-full">
            {{ form.processing ? 'Subscribing…' : 'Subscribe' }}
        </Button>

        <p
            v-if="page.props.flash?.status"
            class="text-sm text-muted-foreground"
        >
            {{ page.props.flash.status }}
        </p>
    </form>
</template>
