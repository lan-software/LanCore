<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { update } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import ndsFlag from '@/assets/flags/nds.svg?url';
import sxuFlag from '@/assets/flags/sxu.svg?url';
import tlhFlag from '@/assets/flags/tlh.svg?url';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Props = {
    currentLocale: string;
    availableLocales: string[];
    userName: string;
    userEmail: string;
};

const props = defineProps<Props>();

const LOCALE_LABELS: Record<string, string> = {
    en: 'English',
    de: 'Deutsch',
    fr: 'Français',
    es: 'Español',
    sv: 'Svenska',
    uk: 'Українська',
    ko: '한국어',
    tlh: 'tlhIngan Hol',
    nds: 'Plattdüütsch',
    sxu: 'Sächsisch',
};

const LOCALE_TO_COUNTRY: Record<string, string> = {
    en: 'gb',
    de: 'de',
    fr: 'fr',
    es: 'es',
    sv: 'se',
    uk: 'ua',
    ko: 'kr',
};

const LOCALE_CUSTOM_FLAG: Record<string, string> = {
    tlh: tlhFlag,
    nds: ndsFlag,
    sxu: sxuFlag,
};

const saving = ref(false);
const saved = ref(false);

function onSelect(value: string) {
    if (!value || value === props.currentLocale) {
        return;
    }

    saving.value = true;
    saved.value = false;

    router.patch(
        update().url,
        {
            name: props.userName,
            email: props.userEmail,
            locale: value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                saved.value = true;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-2">
            <Label for="locale">{{ $t('settings.language.label') }}</Label>
            <Select :model-value="currentLocale" @update:model-value="onSelect">
                <SelectTrigger
                    id="locale"
                    class="mt-1 w-full"
                    :disabled="saving"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="code in availableLocales"
                        :key="code"
                        :value="code"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span
                                v-if="LOCALE_CUSTOM_FLAG[code]"
                                class="inline-block h-4 w-6 rounded-sm bg-cover bg-center"
                                :style="{
                                    backgroundImage: `url(${LOCALE_CUSTOM_FLAG[code]})`,
                                }"
                                aria-hidden="true"
                            />
                            <span
                                v-else-if="LOCALE_TO_COUNTRY[code]"
                                :class="[
                                    'fi',
                                    `fi-${LOCALE_TO_COUNTRY[code]}`,
                                    'rounded-sm',
                                ]"
                                aria-hidden="true"
                            />
                            {{ LOCALE_LABELS[code] ?? code.toUpperCase() }}
                        </span>
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <Transition
            enter-active-class="transition ease-in-out"
            enter-from-class="opacity-0"
            leave-active-class="transition ease-in-out"
            leave-to-class="opacity-0"
        >
            <p v-show="saved" class="text-sm text-neutral-600">
                {{ $t('settings.language.saved') }}
            </p>
        </Transition>
    </div>
</template>
