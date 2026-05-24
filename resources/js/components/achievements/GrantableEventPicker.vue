<script setup lang="ts">
import { Check, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Input } from '@/components/ui/input';

const { t } = useI18n();

const props = defineProps<{
    modelValue: string[];
    options: { value: string; label: string }[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const query = ref('');

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();

    if (q === '') {
        return props.options;
    }

    return props.options.filter((opt) => {
        return (
            opt.label.toLowerCase().includes(q) ||
            opt.value.toLowerCase().includes(q)
        );
    });
});

const selectedCount = computed(() => props.modelValue.length);

function toggle(value: string) {
    const current = props.modelValue;
    const idx = current.indexOf(value);
    emit(
        'update:modelValue',
        idx === -1 ? [...current, value] : current.filter((v) => v !== value),
    );
}

function isSelected(value: string): boolean {
    return props.modelValue.includes(value);
}

function clearSelection() {
    emit('update:modelValue', []);
}
</script>

<template>
    <div class="space-y-2">
        <div class="relative">
            <Search
                class="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <Input
                v-model="query"
                type="search"
                class="pl-8"
                :placeholder="t('achievements.trigger.searchPlaceholder')"
                :aria-label="t('achievements.trigger.searchPlaceholder')"
            />
        </div>

        <div
            class="flex items-center justify-between text-xs text-muted-foreground"
        >
            <span>
                {{
                    t('achievements.trigger.countSummary', {
                        selected: selectedCount,
                        total: options.length,
                    })
                }}
            </span>
            <button
                v-if="selectedCount > 0"
                type="button"
                class="text-primary hover:underline"
                @click="clearSelection"
            >
                {{ t('achievements.trigger.clearSelection') }}
            </button>
        </div>

        <div
            class="max-h-72 overflow-y-auto rounded-md border border-input bg-background"
            role="listbox"
            aria-multiselectable="true"
        >
            <p
                v-if="filtered.length === 0"
                class="px-3 py-4 text-center text-xs text-muted-foreground"
            >
                {{ t('achievements.trigger.noMatches') }}
            </p>
            <button
                v-for="opt in filtered"
                v-else
                :key="opt.value"
                type="button"
                role="option"
                :aria-selected="isSelected(opt.value)"
                class="flex w-full items-start gap-2 border-b border-input/40 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-accent focus:outline-none focus-visible:bg-accent"
                :class="{
                    'bg-accent/60': isSelected(opt.value),
                }"
                @click="toggle(opt.value)"
            >
                <span
                    class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded border border-input"
                    :class="
                        isSelected(opt.value)
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'bg-background'
                    "
                >
                    <Check v-if="isSelected(opt.value)" class="size-3" />
                </span>
                <span class="flex flex-1 flex-col">
                    <span class="font-medium">{{ opt.label }}</span>
                    <span class="font-mono text-[10px] text-muted-foreground">
                        {{ opt.value }}
                    </span>
                </span>
            </button>
        </div>
    </div>
</template>
