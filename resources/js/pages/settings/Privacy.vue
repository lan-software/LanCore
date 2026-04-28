<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { update } from '@/actions/App/Http/Controllers/Settings/PrivacyController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/privacy';
import type { BreadcrumbItem } from '@/types';

type ProfileVisibility = 'public' | 'logged_in' | 'private';

interface ConsentAcceptance {
    id: number;
    policy: { key: string; name: string };
    version: { version_number: number; locale: string };
    accepted_at: string | null;
    source: string | null;
}

const props = defineProps<{
    isSeatVisiblePublicly: boolean;
    profileVisibility: ProfileVisibility;
    profileVisibilities: ProfileVisibility[];
    consentAcceptances: ConsentAcceptance[];
}>();

const withdrawTarget = ref<ConsentAcceptance | null>(null);
const withdrawReason = ref('');
const withdrawSubmitting = ref(false);

function openWithdraw(acceptance: ConsentAcceptance): void {
    withdrawTarget.value = acceptance;
    withdrawReason.value = '';
}

function closeWithdraw(): void {
    withdrawTarget.value = null;
    withdrawReason.value = '';
}

function confirmWithdraw(): void {
    if (!withdrawTarget.value) {
        return;
    }

    withdrawSubmitting.value = true;
    router.post(
        `/settings/consent/${withdrawTarget.value.policy.key}/withdraw`,
        { reason: withdrawReason.value || null },
        {
            preserveScroll: true,
            onFinish: () => {
                withdrawSubmitting.value = false;
                closeWithdraw();
            },
        },
    );
}

const { t } = useI18n();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('settings.privacy.title'),
        href: edit(),
    },
];

const form = useForm({
    is_seat_visible_publicly: props.isSeatVisiblePublicly,
    profile_visibility: props.profileVisibility,
});

function visibilityLabel(value: ProfileVisibility): string {
    if (value === 'public') {
        return t('settings.privacy.profileVisibilityPublic');
    }

    if (value === 'private') {
        return t('settings.privacy.profileVisibilityPrivate');
    }

    return t('settings.privacy.profileVisibilityLoggedIn');
}

function submit(): void {
    form.patch(update().url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('settings.privacy.title')" />

        <h1 class="sr-only">{{ $t('settings.privacy.srHeading') }}</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    :title="$t('settings.privacy.title')"
                    :description="$t('settings.privacy.description')"
                />

                <form @submit.prevent="submit" class="space-y-8">
                    <fieldset class="space-y-4">
                        <legend class="text-sm font-semibold">
                            {{
                                $t('settings.privacy.profileVisibilityHeading')
                            }}
                        </legend>
                        <p class="text-sm text-muted-foreground">
                            {{
                                $t(
                                    'settings.privacy.profileVisibilityDescription',
                                )
                            }}
                        </p>

                        <div class="space-y-2">
                            <label
                                v-for="value in props.profileVisibilities"
                                :key="value"
                                class="flex cursor-pointer items-start gap-3 rounded-md border border-border p-3 hover:bg-muted"
                                :class="{
                                    'border-primary bg-muted/40':
                                        form.profile_visibility === value,
                                }"
                            >
                                <input
                                    type="radio"
                                    :value="value"
                                    v-model="form.profile_visibility"
                                    name="profile_visibility"
                                    class="mt-1"
                                />
                                <span class="text-sm">{{
                                    visibilityLabel(value)
                                }}</span>
                            </label>
                        </div>

                        <InputError :message="form.errors.profile_visibility" />
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="text-sm font-semibold">
                            {{ $t('settings.privacy.seatVisibilityHeading') }}
                        </legend>

                        <div class="flex items-center gap-3">
                            <Switch
                                id="seat-visible-publicly"
                                v-model="form.is_seat_visible_publicly"
                            />
                            <Label
                                for="seat-visible-publicly"
                                class="cursor-pointer select-none"
                            >
                                {{ $t('settings.privacy.seatVisibleLabel') }}
                            </Label>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            {{ $t('settings.privacy.seatVisibleHelp') }}
                        </p>

                        <InputError
                            :message="form.errors.is_seat_visible_publicly"
                        />
                    </fieldset>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing
                                    ? $t('common.saving')
                                    : $t('common.save')
                            }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                {{ $t('common.saved') }}
                            </p>
                        </Transition>
                    </div>
                </form>

                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold">
                        {{ $t('settings.privacy.consent.heading') }}
                    </legend>
                    <p class="text-sm text-muted-foreground">
                        {{ $t('settings.privacy.consent.description') }}
                    </p>

                    <ul
                        v-if="props.consentAcceptances.length"
                        class="space-y-2"
                    >
                        <li
                            v-for="acceptance in props.consentAcceptances"
                            :key="acceptance.id"
                            class="flex flex-wrap items-center justify-between gap-3 rounded-md border bg-card p-3"
                        >
                            <div class="text-sm">
                                <div class="font-medium">
                                    {{ acceptance.policy.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    v{{ acceptance.version.version_number }} ({{
                                        acceptance.version.locale
                                    }})
                                    <span v-if="acceptance.accepted_at">
                                        ·
                                        {{
                                            new Date(
                                                acceptance.accepted_at,
                                            ).toLocaleString()
                                        }}
                                    </span>
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="openWithdraw(acceptance)"
                            >
                                {{ $t('settings.privacy.consent.withdraw') }}
                            </Button>
                        </li>
                    </ul>

                    <p v-else class="text-sm text-muted-foreground italic">
                        {{ $t('settings.privacy.consent.empty') }}
                    </p>
                </fieldset>
            </div>
        </SettingsLayout>

        <Dialog
            :open="withdrawTarget !== null"
            @update:open="(v) => !v && closeWithdraw()"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{
                            $t(
                                'settings.privacy.consent.withdraw_dialog.title',
                                {
                                    name: withdrawTarget?.policy.name ?? '',
                                },
                            )
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            $t(
                                'settings.privacy.consent.withdraw_dialog.description',
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="withdraw-reason">
                        {{
                            $t(
                                'settings.privacy.consent.withdraw_dialog.reason_label',
                            )
                        }}
                    </Label>
                    <Textarea
                        id="withdraw-reason"
                        v-model="withdrawReason"
                        rows="3"
                    />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="withdrawSubmitting"
                        @click="closeWithdraw"
                    >
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="withdrawSubmitting"
                        @click="confirmWithdraw"
                    >
                        {{
                            withdrawSubmitting
                                ? $t('common.saving')
                                : $t(
                                      'settings.privacy.consent.withdraw_dialog.confirm',
                                  )
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
