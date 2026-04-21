<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { update } from '@/actions/App/Http/Controllers/Settings/ProfileController';
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
                        {{ LOCALE_LABELS[code] ?? code.toUpperCase() }}
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
