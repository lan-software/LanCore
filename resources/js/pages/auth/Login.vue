<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SteamIcon from '@/components/icons/SteamIcon.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { redirect as steamRedirect } from '@/routes/auth/steam';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        :title="$t('auth.login.title')"
        :description="$t('auth.login.description')"
    >
        <Head :title="$t('auth.login.button')" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ $t('auth.login.email') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        :placeholder="$t('auth.login.emailPlaceholder')"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{
                            $t('auth.login.password')
                        }}</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm"
                            :tabindex="5"
                        >
                            {{ $t('auth.login.forgotPassword') }}
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        :placeholder="$t('auth.login.passwordPlaceholder')"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>{{ $t('auth.login.rememberMe') }}</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    {{ $t('auth.login.button') }}
                </Button>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-border"></span>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-background px-2 text-muted-foreground">
                            {{ $t('auth.divider.or') }}
                        </span>
                    </div>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    class="w-full"
                    as-child
                    data-test="steam-signin-button"
                >
                    <a :href="steamRedirect().url">
                        <SteamIcon :size="16" />
                        {{ $t('auth.steam.signInWith') }}
                    </a>
                </Button>
            </div>

            <div
                class="text-center text-sm text-muted-foreground"
                v-if="canRegister"
            >
                {{ $t('auth.login.noAccount') }}
                <TextLink :href="register()" :tabindex="5">{{
                    $t('auth.login.signUp')
                }}</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
