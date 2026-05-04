<script setup lang="ts">
/**
 * Pairs a native color input with a hex text input. Both kept in sync via
 * v-model. Empty string represents "fall back to platform default".
 */
import { computed } from 'vue';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    modelValue: string;
    label: string;
    fallback?: string;
    id?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const HEX_RE = /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/;

const colorValue = computed<string>({
    get: () =>
        HEX_RE.test(props.modelValue.trim())
            ? props.modelValue.trim().toLowerCase()
            : (props.fallback ?? '#000000'),
    set: (v: string) => emit('update:modelValue', v),
});

function clear() {
    emit('update:modelValue', '');
}
</script>

<template>
    <div class="flex items-center gap-2">
        <input
            :id="id"
            type="color"
            :value="colorValue"
            class="h-9 w-9 cursor-pointer rounded-md border border-input bg-transparent p-0.5"
            :aria-label="label"
            @input="
                (e) =>
                    emit(
                        'update:modelValue',
                        (e.target as HTMLInputElement).value,
                    )
            "
        />
        <Input
            :model-value="modelValue"
            :placeholder="fallback ?? 'inherit'"
            class="h-9 flex-1 font-mono text-xs"
            @update:model-value="(v) => emit('update:modelValue', String(v))"
        />
        <button
            v-if="modelValue !== ''"
            type="button"
            class="text-xs text-muted-foreground hover:text-foreground"
            @click="clear"
        >
            reset
        </button>
    </div>
</template>
