<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import confetti from 'canvas-confetti';
import { CheckCircle2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
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
import type { AvailableLocale } from '@/i18n';

type Props = {
    currentLocale: string;
    availableLocales: string[];
    userName: string;
    userEmail: string;
};

const props = defineProps<Props>();

const { locale: i18nLocale, availableLocales: i18nAvailable } = useI18n();

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
let savedResetTimer: ReturnType<typeof setTimeout> | null = null;

function fireSuccessConfetti() {
    if (typeof window === 'undefined') {
        return;
    }

    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    confetti({
        particleCount: 80,
        spread: 60,
        startVelocity: 35,
        ticks: 140,
        origin: { y: 0.35 },
        colors: ['#22c55e', '#10b981', '#34d399', '#a7f3d0'],
        disableForReducedMotion: true,
    });
}

function applyLocaleClientSide(value: string) {
    if (i18nAvailable.includes(value as AvailableLocale)) {
        i18nLocale.value = value as AvailableLocale;
    }

    if (typeof document !== 'undefined') {
        document.documentElement.lang = value;
    }
}

function onSelect(value: string) {
    if (!value || value === props.currentLocale) {
        return;
    }

    saving.value = true;
    saved.value = false;

    if (savedResetTimer) {
        clearTimeout(savedResetTimer);
        savedResetTimer = null;
    }

    router.patch(
        update().url,
        {
            name: props.userName,
            email: props.userEmail,
            locale: value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                applyLocaleClientSide(value);
                saved.value = true;
                fireSuccessConfetti();
                savedResetTimer = setTimeout(() => {
                    saved.value = false;
                }, 3500);
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
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <p
                v-show="saved"
                class="flex items-center gap-1.5 text-sm text-green-600 dark:text-green-400"
                role="status"
                aria-live="polite"
            >
                <CheckCircle2 class="size-4" />
                {{ $t('settings.language.saved') }}
            </p>
        </Transition>
    </div>
</template>
