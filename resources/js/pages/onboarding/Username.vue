<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { update } from '@/routes/onboarding/username';

const username = ref('');
</script>

<template>
    <AuthBase
        :title="$t('onboarding.username.title')"
        :description="$t('onboarding.username.description')"
    >
        <Head :title="$t('onboarding.username.title')" />

        <Form
            v-bind="update.form()"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-2">
                <Label for="username">{{
                    $t('onboarding.username.label')
                }}</Label>
                <Input
                    id="username"
                    name="username"
                    type="text"
                    required
                    autofocus
                    pattern="[A-Za-z0-9][A-Za-z0-9_-]{1,30}[A-Za-z0-9]"
                    minlength="3"
                    maxlength="32"
                    autocomplete="username"
                    v-model="username"
                    :placeholder="$t('onboarding.username.placeholder')"
                />
                <p class="text-xs text-muted-foreground">
                    {{ $t('onboarding.username.hint') }}
                </p>
                <InputError :message="errors.username" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :disabled="processing"
                data-test="onboarding-username-button"
            >
                <Spinner v-if="processing" />
                {{ $t('onboarding.username.submit') }}
            </Button>
        </Form>
    </AuthBase>
</template>
