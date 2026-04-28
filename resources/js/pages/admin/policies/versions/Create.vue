<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import NonEditorialChangeConfirmDialog from '@/components/policies/NonEditorialChangeConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    policy: {
        id: number;
        name: string;
        key: string;
        type: { label: string } | null;
    };
    priorAcceptorCount: number;
}>();

const form = useForm({
    content: '',
    is_non_editorial_change: false as boolean,
    public_statement: '',
    locale: '',
    effective_at: '',
});

const dialogOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Policies', href: '/admin/policies' },
    { title: props.policy.name, href: `/admin/policies/${props.policy.id}` },
    {
        title: 'Publish version',
        href: `/admin/policies/${props.policy.id}/versions/create`,
    },
];

function onSubmit(): void {
    if (form.is_non_editorial_change) {
        dialogOpen.value = true;

        return;
    }

    submitNow();
}

function submitNow(): void {
    form.post(`/admin/policies/${props.policy.id}/versions`);
}
</script>

<template>
    <Head :title="`Publish new version — ${policy.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="`/admin/policies/${policy.id}`"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to {{ policy.name }}
                </Link>
            </div>

            <Heading
                :title="`Publish new version of ${policy.name}`"
                description="Markdown is rendered into a PDF snapshot at publish time."
            />

            <form class="space-y-4" @submit.prevent="onSubmit">
                <div class="grid gap-2">
                    <Label for="content">Content (Markdown)</Label>
                    <Textarea
                        id="content"
                        v-model="form.content"
                        rows="20"
                        required
                        class="font-mono text-sm"
                    />
                    <InputError :message="form.errors.content" />
                </div>

                <div class="grid gap-2">
                    <Label for="locale"
                        >Locale (optional — defaults to app locale)</Label
                    >
                    <Input
                        id="locale"
                        v-model="form.locale"
                        placeholder="e.g. de or en"
                        maxlength="10"
                    />
                    <InputError :message="form.errors.locale" />
                </div>

                <div class="grid gap-2">
                    <Label for="effective_at"
                        >Effective at (optional — defaults to now)</Label
                    >
                    <Input
                        id="effective_at"
                        v-model="form.effective_at"
                        type="datetime-local"
                    />
                    <InputError :message="form.errors.effective_at" />
                </div>

                <div
                    class="rounded-md border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30"
                >
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_non_editorial_change"
                            v-model="form.is_non_editorial_change"
                        />
                        <Label
                            for="is_non_editorial_change"
                            class="cursor-pointer font-medium"
                        >
                            This is a non-editorial change
                        </Label>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Tick this only when the change affects user rights. It
                        will email all
                        <strong>{{ priorAcceptorCount }}</strong>
                        prior acceptors and force re-acceptance on next login.
                    </p>
                </div>

                <div v-if="form.is_non_editorial_change" class="grid gap-2">
                    <Label for="public_statement">
                        Public statement (included in the email)
                    </Label>
                    <Textarea
                        id="public_statement"
                        v-model="form.public_statement"
                        rows="4"
                        placeholder="Explain why the policy is changing and what it means for the user's rights."
                    />
                    <InputError :message="form.errors.public_statement" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Publishing…' : 'Publish' }}
                    </Button>
                </div>
            </form>

            <NonEditorialChangeConfirmDialog
                v-model:open="dialogOpen"
                :prior-acceptor-count="priorAcceptorCount"
                @confirmed="submitNow"
            />
        </div>
    </AppLayout>
</template>
