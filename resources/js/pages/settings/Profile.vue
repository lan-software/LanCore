<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, ImagePlus, Loader2, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ProfileEmojiPicker from '@/components/ProfileEmojiPicker.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit, preview as previewRoute } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

type AvatarSource = 'default' | 'gravatar' | 'custom' | 'steam';
type ProfileVisibility = 'public' | 'logged_in' | 'private';

type ProfilePayload = {
    username: string | null;
    short_bio: string | null;
    profile_description: string | null;
    profile_emoji: string | null;
    avatar_source: AvatarSource;
    avatar_url: string;
    banner_url: string | null;
    profile_visibility: ProfileVisibility;
};

const props = defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
    profileAlert?: string | null;
    profile: ProfilePayload;
    avatarSources: AvatarSource[];
    profileVisibilities: ProfileVisibility[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit(),
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const currentLocale = computed(
    () =>
        (user.value.locale as string | undefined) ??
        (page.props.locale as string),
);
const availableLocales = computed(
    () => page.props.availableLocales as string[],
);

const countries = [
    { code: 'AT', name: 'Austria' },
    { code: 'BE', name: 'Belgium' },
    { code: 'CH', name: 'Switzerland' },
    { code: 'CZ', name: 'Czech Republic' },
    { code: 'DE', name: 'Germany' },
    { code: 'DK', name: 'Denmark' },
    { code: 'ES', name: 'Spain' },
    { code: 'FI', name: 'Finland' },
    { code: 'FR', name: 'France' },
    { code: 'GB', name: 'United Kingdom' },
    { code: 'HU', name: 'Hungary' },
    { code: 'IE', name: 'Ireland' },
    { code: 'IT', name: 'Italy' },
    { code: 'LU', name: 'Luxembourg' },
    { code: 'NL', name: 'Netherlands' },
    { code: 'NO', name: 'Norway' },
    { code: 'PL', name: 'Poland' },
    { code: 'PT', name: 'Portugal' },
    { code: 'SE', name: 'Sweden' },
    { code: 'SK', name: 'Slovakia' },
];

const avatarInput = ref<HTMLInputElement>();
const bannerInput = ref<HTMLInputElement>();
const uploadingAvatar = ref(false);
const uploadingBanner = ref(false);
const mediaError = ref<string | null>(null);
const emoji = ref<string | null>(props.profile.profile_emoji);

function avatarSourceLabel(source: AvatarSource): string {
    if (source === 'gravatar') {
        return 'settings.profile.avatarSourceGravatar';
    }

    if (source === 'custom') {
        return 'settings.profile.avatarSourceCustom';
    }

    if (source === 'steam') {
        return 'settings.profile.avatarSourceSteam';
    }

    return 'settings.profile.avatarSourceDefault';
}

function triggerAvatarUpload(): void {
    avatarInput.value?.click();
}

function triggerBannerUpload(): void {
    bannerInput.value?.click();
}

function uploadAvatar(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    mediaError.value = null;
    uploadingAvatar.value = true;
    router.post(
        '/settings/profile/avatar',
        { image: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                mediaError.value = (errors.image as string | undefined) ?? null;
            },
            onFinish: () => {
                uploadingAvatar.value = false;

                if (avatarInput.value) {
                    avatarInput.value.value = '';
                }
            },
        },
    );
}

function removeAvatar(): void {
    router.delete('/settings/profile/avatar', { preserveScroll: true });
}

function uploadBanner(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    mediaError.value = null;
    uploadingBanner.value = true;
    router.post(
        '/settings/profile/banner',
        { image: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                mediaError.value = (errors.image as string | undefined) ?? null;
            },
            onFinish: () => {
                uploadingBanner.value = false;

                if (bannerInput.value) {
                    bannerInput.value.value = '';
                }
            },
        },
    );
}

function removeBanner(): void {
    router.delete('/settings/profile/banner', { preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-8">
                <Alert v-if="profileAlert" data-test="profile-alert">
                    <AlertCircle class="size-4" />
                    <AlertTitle>Complete your profile to continue</AlertTitle>
                    <AlertDescription>{{ profileAlert }}</AlertDescription>
                </Alert>

                <Heading
                    variant="small"
                    :title="$t('settings.profile.customization')"
                    :description="
                        $t('settings.profile.customizationDescription')
                    "
                />

                <div
                    class="overflow-hidden rounded-xl border border-border bg-card"
                >
                    <div class="relative">
                        <div
                            v-if="props.profile.banner_url"
                            class="h-40 w-full bg-cover bg-center"
                            :style="{
                                backgroundImage: `url(${props.profile.banner_url})`,
                            }"
                        />
                        <div
                            v-else
                            class="h-40 w-full bg-gradient-to-br from-fuchsia-500 via-violet-500 to-cyan-500"
                        />

                        <div
                            class="absolute top-3 right-3 flex items-center gap-2"
                        >
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                :disabled="uploadingBanner"
                                @click="triggerBannerUpload"
                            >
                                <Loader2
                                    v-if="uploadingBanner"
                                    class="size-4 animate-spin"
                                />
                                <ImagePlus v-else class="size-4" />
                                {{ $t('settings.profile.bannerUpload') }}
                            </Button>
                            <Button
                                v-if="props.profile.banner_url"
                                type="button"
                                variant="secondary"
                                size="sm"
                                @click="removeBanner"
                            >
                                <Trash2 class="size-4" />
                                {{ $t('settings.profile.bannerRemove') }}
                            </Button>
                        </div>

                        <input
                            ref="bannerInput"
                            type="file"
                            class="hidden"
                            accept="image/png,image/jpeg,image/webp"
                            @change="uploadBanner"
                        />
                    </div>

                    <div class="relative flex items-end gap-4 px-6 pb-6">
                        <div class="relative z-10 -mt-12">
                            <img
                                :src="props.profile.avatar_url"
                                alt=""
                                class="size-24 rounded-2xl border-4 border-card object-cover shadow-lg"
                            />
                        </div>
                        <div class="flex flex-1 flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="uploadingAvatar"
                                @click="triggerAvatarUpload"
                            >
                                <Loader2
                                    v-if="uploadingAvatar"
                                    class="size-4 animate-spin"
                                />
                                <ImagePlus v-else class="size-4" />
                                {{ $t('settings.profile.avatarUpload') }}
                            </Button>
                            <Button
                                v-if="props.profile.avatar_source === 'custom'"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="removeAvatar"
                            >
                                <Trash2 class="size-4" />
                                {{ $t('settings.profile.avatarRemove') }}
                            </Button>

                            <input
                                ref="avatarInput"
                                type="file"
                                class="hidden"
                                accept="image/png,image/jpeg,image/webp"
                                @change="uploadAvatar"
                            />
                        </div>
                    </div>

                    <p class="px-6 pb-4 text-xs text-muted-foreground">
                        {{ $t('settings.profile.avatarUploadHint') }}
                        <br />
                        {{ $t('settings.profile.bannerUploadHint') }}
                    </p>

                    <InputError v-if="mediaError" :message="mediaError" />
                </div>

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="username">{{
                            $t('settings.profile.username')
                        }}</Label>
                        <Input
                            id="username"
                            class="mt-1 block w-full"
                            name="username"
                            :default-value="props.profile.username ?? ''"
                            autocomplete="username"
                            pattern="[A-Za-z0-9][A-Za-z0-9_-]{1,30}[A-Za-z0-9]"
                            minlength="3"
                            maxlength="32"
                            :placeholder="
                                $t('settings.profile.usernamePlaceholder')
                            "
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ $t('settings.profile.usernameHint') }}
                        </p>
                        <InputError class="mt-2" :message="errors.username" />
                    </div>

                    <div class="grid gap-2">
                        <Label>{{ $t('settings.profile.profileEmoji') }}</Label>
                        <ProfileEmojiPicker
                            v-model="emoji"
                            name="profile_emoji"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ $t('settings.profile.profileEmojiHint') }}
                        </p>
                        <InputError
                            class="mt-2"
                            :message="errors.profile_emoji"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="short_bio">{{
                            $t('settings.profile.shortBio')
                        }}</Label>
                        <Input
                            id="short_bio"
                            class="mt-1 block w-full"
                            name="short_bio"
                            :default-value="props.profile.short_bio ?? ''"
                            maxlength="160"
                            :placeholder="
                                $t('settings.profile.shortBioPlaceholder')
                            "
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ $t('settings.profile.shortBioHint') }}
                        </p>
                        <InputError class="mt-2" :message="errors.short_bio" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="profile_description">{{
                            $t('settings.profile.profileDescription')
                        }}</Label>
                        <Textarea
                            id="profile_description"
                            class="mt-1 block min-h-32 w-full"
                            name="profile_description"
                            :default-value="
                                props.profile.profile_description ?? ''
                            "
                            maxlength="5000"
                            :placeholder="
                                $t(
                                    'settings.profile.profileDescriptionPlaceholder',
                                )
                            "
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ $t('settings.profile.profileDescriptionHint') }}
                        </p>
                        <InputError
                            class="mt-2"
                            :message="errors.profile_description"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="avatar_source">{{
                            $t('settings.profile.avatarSource')
                        }}</Label>
                        <Select
                            name="avatar_source"
                            :default-value="props.profile.avatar_source"
                        >
                            <SelectTrigger
                                id="avatar_source"
                                class="mt-1 w-full"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="source in props.avatarSources"
                                    :key="source"
                                    :value="source"
                                    :disabled="source === 'steam'"
                                >
                                    {{ $t(avatarSourceLabel(source)) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            class="mt-2"
                            :message="errors.avatar_source"
                        />
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-4 border-t border-border pt-4"
                    >
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >{{ $t('settings.profile.updateButton') }}</Button
                        >

                        <a
                            :href="previewRoute().url"
                            target="_blank"
                            rel="noopener"
                            class="text-sm text-primary underline-offset-4 hover:underline"
                        >
                            {{ $t('settings.profile.previewProfile') }}
                        </a>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                {{ $t('settings.profile.updated') }}
                            </p>
                        </Transition>
                    </div>

                    <Heading
                        variant="small"
                        :title="$t('settings.profile.contactSection')"
                        :description="
                            $t('settings.profile.contactSectionDescription')
                        "
                    />

                    <div class="grid gap-2">
                        <Label for="name">{{
                            $t('settings.profile.name')
                        }}</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">{{
                            $t('settings.profile.email')
                        }}</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="email"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            {{ $t('settings.profile.emailUnverified') }}
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                {{ $t('settings.profile.resendLink') }}
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            {{ $t('settings.profile.verificationSent') }}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">{{
                            $t('settings.profile.phone')
                        }}</Label>
                        <Input
                            id="phone"
                            type="tel"
                            class="mt-1 block w-full"
                            name="phone"
                            :default-value="(user.phone as string) ?? ''"
                            autocomplete="tel"
                            :placeholder="
                                $t('settings.profile.phonePlaceholder')
                            "
                        />
                        <InputError class="mt-2" :message="errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="street">{{
                            $t('settings.profile.street')
                        }}</Label>
                        <Input
                            id="street"
                            class="mt-1 block w-full"
                            name="street"
                            :default-value="(user.street as string) ?? ''"
                            autocomplete="street-address"
                            :placeholder="
                                $t('settings.profile.streetPlaceholder')
                            "
                        />
                        <InputError class="mt-2" :message="errors.street" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="zip_code">{{
                                $t('settings.profile.zipCode')
                            }}</Label>
                            <Input
                                id="zip_code"
                                class="mt-1 block w-full"
                                name="zip_code"
                                :default-value="(user.zip_code as string) ?? ''"
                                autocomplete="postal-code"
                                :placeholder="
                                    $t('settings.profile.zipCodePlaceholder')
                                "
                            />
                            <InputError
                                class="mt-2"
                                :message="errors.zip_code"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="city">{{
                                $t('settings.profile.city')
                            }}</Label>
                            <Input
                                id="city"
                                class="mt-1 block w-full"
                                name="city"
                                :default-value="(user.city as string) ?? ''"
                                autocomplete="address-level2"
                                :placeholder="
                                    $t('settings.profile.cityPlaceholder')
                                "
                            />
                            <InputError class="mt-2" :message="errors.city" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="country">{{
                            $t('settings.profile.country')
                        }}</Label>
                        <Select
                            name="country"
                            :default-value="(user.country as string) ?? ''"
                        >
                            <SelectTrigger id="country" class="mt-1 w-full">
                                <SelectValue
                                    :placeholder="
                                        $t(
                                            'settings.profile.countryPlaceholder',
                                        )
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="c in countries"
                                    :key="c.code"
                                    :value="c.code"
                                >
                                    {{ c.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError class="mt-2" :message="errors.country" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-contact-button"
                            >{{ $t('settings.profile.updateButton') }}</Button
                        >
                    </div>
                </Form>

                <Heading
                    variant="small"
                    :title="$t('settings.language.title')"
                    :description="$t('settings.language.description')"
                />

                <LanguageSwitcher
                    :current-locale="currentLocale"
                    :available-locales="availableLocales"
                    :user-name="user.name as string"
                    :user-email="user.email as string"
                />
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
