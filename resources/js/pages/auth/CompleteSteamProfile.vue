<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import SteamIcon from '@/components/icons/SteamIcon.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { complete as completeSteam } from '@/routes/auth/steam';
import { show as showPolicy } from '@/routes/policies';

interface PendingSteamProfile {
    steam_id_64: string;
    persona_name: string | null;
    avatar_url: string | null;
    profile_url: string | null;
    country_code: string | null;
}

interface RequiredPolicy {
    id: number;
    policy_id: number;
    policy_key: string | null;
    policy_name: string | null;
    policy_description: string | null;
    version_number: number;
    locale: string;
}

const props = defineProps<{
    pending: PendingSteamProfile;
    suggestedUsername: string | null;
    requiredPolicies: RequiredPolicy[];
}>();

const acceptedPolicyIds = ref<number[]>([]);

function togglePolicy(id: number, checked: boolean | string): void {
    if (checked) {
        if (!acceptedPolicyIds.value.includes(id)) {
            acceptedPolicyIds.value = [...acceptedPolicyIds.value, id];
        }
    } else {
        acceptedPolicyIds.value = acceptedPolicyIds.value.filter(
            (existing) => existing !== id,
        );
    }
}
</script>

<template>
    <AuthBase
        :title="$t('auth.steam.completeProfile.title')"
        :description="$t('auth.steam.completeProfile.intro')"
    >
        <Head :title="$t('auth.steam.completeProfile.title')" />

        <div
            class="mb-6 flex items-center gap-4 rounded-md border border-border bg-card p-3"
        >
            <img
                v-if="props.pending.avatar_url"
                :src="props.pending.avatar_url"
                :alt="props.pending.persona_name ?? ''"
                class="h-12 w-12 rounded-full"
            />
            <div class="flex flex-col text-sm">
                <span class="flex items-center gap-2 font-medium">
                    <SteamIcon :size="14" />
                    {{
                        props.pending.persona_name ??
                        $t('auth.steam.completeProfile.unknownSteamUser')
                    }}
                </span>
                <span class="text-xs text-muted-foreground">
                    Steam ID: {{ props.pending.steam_id_64 }}
                </span>
            </div>
        </div>

        <Form
            :action="completeSteam.url()"
            method="post"
            :reset-on-success="['email']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">
                        {{ $t('auth.register.name') }}
                    </Label>
                    <Input
                        id="name"
                        name="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        :default-value="props.pending.persona_name ?? ''"
                        autocomplete="name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="username">
                        {{ $t('auth.register.username') }}
                    </Label>
                    <Input
                        id="username"
                        name="username"
                        type="text"
                        required
                        :tabindex="2"
                        :default-value="props.suggestedUsername ?? ''"
                        autocomplete="username"
                        pattern="[A-Za-z0-9][A-Za-z0-9_-]{1,30}[A-Za-z0-9]"
                        minlength="3"
                        maxlength="32"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ $t('auth.register.usernameHint') }}
                    </p>
                    <InputError :message="errors.username" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">
                        {{ $t('auth.register.email') }}
                    </Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        required
                        :tabindex="3"
                        autocomplete="email"
                        :placeholder="$t('auth.register.emailPlaceholder')"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ $t('auth.steam.completeProfile.emailHint') }}
                    </p>
                    <InputError :message="errors.email" />
                </div>

                <fieldset
                    v-if="props.requiredPolicies.length > 0"
                    class="grid gap-3"
                >
                    <legend class="text-sm font-semibold">
                        {{ $t('auth.steam.completeProfile.policiesHeading') }}
                    </legend>
                    <div
                        v-for="policy in props.requiredPolicies"
                        :key="policy.id"
                        class="rounded-md border border-border p-3"
                    >
                        <Label class="flex cursor-pointer items-start gap-3">
                            <Checkbox
                                :name="`accepted_policy_version_ids[${policy.id}]`"
                                :value="policy.id"
                                :checked="acceptedPolicyIds.includes(policy.id)"
                                @update:model-value="
                                    (v) => togglePolicy(policy.id, v)
                                "
                            />
                            <span class="text-sm">
                                <span class="font-medium">{{
                                    policy.policy_name
                                }}</span>
                                <span
                                    v-if="policy.policy_description"
                                    class="block text-xs text-muted-foreground"
                                >
                                    {{ policy.policy_description }}
                                </span>
                            </span>
                        </Label>
                        <a
                            v-if="policy.policy_key"
                            :href="showPolicy(policy.policy_key).url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-2 ml-7 inline-block text-xs underline underline-offset-2 hover:text-foreground"
                        >
                            {{ $t('auth.steam.completeProfile.readPolicy') }}
                        </a>
                    </div>
                    <InputError :message="errors.accepted_policy_version_ids" />
                </fieldset>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :disabled="processing"
                    data-test="steam-complete-button"
                >
                    <Spinner v-if="processing" />
                    {{ $t('auth.steam.completeProfile.button') }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                <TextLink :href="login()">
                    {{ $t('auth.login.button') }}
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
