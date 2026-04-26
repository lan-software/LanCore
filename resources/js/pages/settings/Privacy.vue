<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { update } from '@/actions/App/Http/Controllers/Settings/PrivacyController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/privacy';
import type { BreadcrumbItem } from '@/types';

type ProfileVisibility = 'public' | 'logged_in' | 'private';

const props = defineProps<{
    isSeatVisiblePublicly: boolean;
    profileVisibility: ProfileVisibility;
    profileVisibilities: ProfileVisibility[];
}>();

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
                                $t(
                                    'settings.privacy.profileVisibilityHeading',
                                )
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

                        <InputError
                            :message="form.errors.profile_visibility"
                        />
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="text-sm font-semibold">
                            {{
                                $t('settings.privacy.seatVisibilityHeading')
                            }}
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
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
