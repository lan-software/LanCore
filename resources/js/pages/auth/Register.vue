<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SteamIcon from '@/components/icons/SteamIcon.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { redirect as steamRedirect } from '@/routes/auth/steam';
import { store } from '@/routes/register';
</script>

<template>
    <AuthBase
        :title="$t('auth.register.title')"
        :description="$t('auth.register.description')"
    >
        <Head :title="$t('auth.register.button')" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">{{ $t('auth.register.name') }}</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        :placeholder="$t('auth.register.namePlaceholder')"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="username">{{
                        $t('auth.register.username')
                    }}</Label>
                    <Input
                        id="username"
                        type="text"
                        required
                        :tabindex="2"
                        autocomplete="username"
                        name="username"
                        pattern="[A-Za-z0-9][A-Za-z0-9_-]{1,30}[A-Za-z0-9]"
                        minlength="3"
                        maxlength="32"
                        :placeholder="$t('auth.register.usernamePlaceholder')"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ $t('auth.register.usernameHint') }}
                    </p>
                    <InputError :message="errors.username" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ $t('auth.register.email') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="3"
                        autocomplete="email"
                        name="email"
                        :placeholder="$t('auth.register.emailPlaceholder')"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{
                        $t('auth.register.password')
                    }}</Label>
                    <PasswordInput
                        id="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password"
                        :placeholder="$t('auth.register.passwordPlaceholder')"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{
                        $t('auth.register.confirmPassword')
                    }}</Label>
                    <PasswordInput
                        id="password_confirmation"
                        required
                        :tabindex="5"
                        autocomplete="new-password"
                        name="password_confirmation"
                        :placeholder="$t('auth.register.confirmPassword')"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="6"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    {{ $t('auth.register.button') }}
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
                    data-test="steam-signup-button"
                >
                    <a :href="steamRedirect().url">
                        <SteamIcon :size="16" />
                        {{ $t('auth.steam.signUpWith') }}
                    </a>
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ $t('auth.register.hasAccount') }}
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="7"
                    >{{ $t('auth.register.logIn') }}</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>
