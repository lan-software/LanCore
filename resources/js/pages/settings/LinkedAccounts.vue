<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { edit as editLinkedAccounts } from '@/actions/App/Http/Controllers/Settings/LinkedAccountsController';
import Heading from '@/components/Heading.vue';
import SteamIcon from '@/components/icons/SteamIcon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { link as steamLink, unlink as steamUnlink } from '@/routes/auth/steam';
import type { BreadcrumbItem } from '@/types';

interface SteamStatus {
    linked: boolean;
    steam_id_64: string | null;
    linked_at: string | null;
    profile_url: string | null;
}

const props = defineProps<{
    steam: SteamStatus;
    canUnlink: boolean;
    errors?: Record<string, string>;
    status?: string;
}>();

const { t } = useI18n();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('settings.linkedAccounts.title'),
        href: editLinkedAccounts(),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('settings.linkedAccounts.title')" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    :title="$t('settings.linkedAccounts.title')"
                    :description="$t('settings.linkedAccounts.description')"
                />

                <p
                    v-if="props.status === 'steam-linked'"
                    class="rounded-md border border-green-500/40 bg-green-500/10 px-3 py-2 text-sm text-green-700"
                >
                    {{ $t('settings.linkedAccounts.steam.linkedFlash') }}
                </p>
                <p
                    v-if="props.status === 'steam-unlinked'"
                    class="rounded-md border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm text-amber-800"
                >
                    {{ $t('settings.linkedAccounts.steam.unlinkedFlash') }}
                </p>

                <div
                    class="flex flex-col gap-4 rounded-md border border-border bg-card p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <SteamIcon :size="24" />
                        <div class="text-sm">
                            <div class="font-medium">
                                {{
                                    $t('settings.linkedAccounts.steam.heading')
                                }}
                            </div>
                            <div
                                v-if="props.steam.linked"
                                class="text-xs text-muted-foreground"
                            >
                                {{
                                    $t(
                                        'settings.linkedAccounts.steam.linkedDescription',
                                        {
                                            id: props.steam.steam_id_64 ?? '',
                                        },
                                    )
                                }}
                                <a
                                    v-if="props.steam.profile_url"
                                    :href="props.steam.profile_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="ml-1 underline underline-offset-2"
                                >
                                    {{
                                        $t(
                                            'settings.linkedAccounts.steam.viewProfile',
                                        )
                                    }}
                                </a>
                            </div>
                            <div v-else class="text-xs text-muted-foreground">
                                {{
                                    $t(
                                        'settings.linkedAccounts.steam.notLinkedDescription',
                                    )
                                }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-stretch gap-2 sm:items-end">
                        <Form
                            v-if="!props.steam.linked"
                            :action="steamLink.url()"
                            method="post"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                variant="default"
                                :disabled="processing"
                                data-test="steam-link-button"
                            >
                                <SteamIcon :size="14" />
                                {{ $t('auth.steam.linkAccount') }}
                            </Button>
                        </Form>

                        <template v-else>
                            <Form
                                :action="steamUnlink.url()"
                                method="post"
                                v-slot="{ processing }"
                            >
                                <Button
                                    type="submit"
                                    variant="outline"
                                    :disabled="processing || !props.canUnlink"
                                    data-test="steam-unlink-button"
                                >
                                    {{ $t('auth.steam.unlinkAccount') }}
                                </Button>
                            </Form>
                            <p
                                v-if="!props.canUnlink"
                                class="text-xs text-muted-foreground"
                            >
                                {{ $t('auth.steam.unlinkRequiresPassword') }}
                            </p>
                        </template>

                        <InputError :message="props.errors?.steam" />
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
