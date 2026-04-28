<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();

const form = useForm({
    content: '',
    is_non_editorial_change: false as boolean,
    public_statement: '',
    locale: '',
    effective_at: '',
});

const dialogOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('policies.admin.index.title'), href: '/admin/policies' },
    { title: props.policy.name, href: `/admin/policies/${props.policy.id}` },
    {
        title: t('policies.admin.version_create.title', {
            name: props.policy.name,
        }),
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
    <Head
        :title="
            $t('policies.admin.version_create.title', { name: policy.name })
        "
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="`/admin/policies/${policy.id}`"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{
                        $t('policies.admin.version_create.back', {
                            name: policy.name,
                        })
                    }}
                </Link>
            </div>

            <Heading
                :title="
                    $t('policies.admin.version_create.title', {
                        name: policy.name,
                    })
                "
                :description="$t('policies.admin.version_create.description')"
            />

            <form class="space-y-4" @submit.prevent="onSubmit">
                <div class="grid gap-2">
                    <Label for="content">
                        {{ $t('policies.admin.version_create.content') }}
                    </Label>
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
                    <Label for="locale">
                        {{ $t('policies.admin.version_create.locale') }}
                    </Label>
                    <Input
                        id="locale"
                        v-model="form.locale"
                        :placeholder="
                            $t(
                                'policies.admin.version_create.locale_placeholder',
                            )
                        "
                        maxlength="10"
                    />
                    <InputError :message="form.errors.locale" />
                </div>

                <div class="grid gap-2">
                    <Label for="effective_at">
                        {{ $t('policies.admin.version_create.effective_at') }}
                    </Label>
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
                            {{
                                $t(
                                    'policies.admin.version_create.is_non_editorial',
                                )
                            }}
                        </Label>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{
                            $t(
                                'policies.admin.version_create.is_non_editorial_help',
                                { count: priorAcceptorCount },
                            )
                        }}
                    </p>
                </div>

                <div v-if="form.is_non_editorial_change" class="grid gap-2">
                    <Label for="public_statement">
                        {{
                            $t('policies.admin.version_create.public_statement')
                        }}
                    </Label>
                    <Textarea
                        id="public_statement"
                        v-model="form.public_statement"
                        rows="4"
                        :placeholder="
                            $t(
                                'policies.admin.version_create.public_statement_placeholder',
                            )
                        "
                    />
                    <InputError :message="form.errors.public_statement" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{
                            form.processing
                                ? $t('policies.admin.version_create.publishing')
                                : $t('policies.admin.version_create.publish')
                        }}
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
