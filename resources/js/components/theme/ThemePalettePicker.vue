<script setup lang="ts">
/**
 * Renders a grouped grid of `ColorPickerInput` controls, one per CSS
 * variable in the curated palette schema. Two-way binds to a flat
 * `Record<string, string>` map keyed by CSS variable name.
 *
 * @see docs/mil-std-498/SDD.md §5.11
 */
import ColorPickerInput from '@/components/theme/ColorPickerInput.vue';
import { Label } from '@/components/ui/label';
import type {
    PaletteVariableGroup,
    PaletteVariablesSchema,
} from '@/types/theme-editor';

const props = defineProps<{
    schema: PaletteVariablesSchema;
    modelValue: Record<string, string>;
    /** Map of CSS variable name -> placeholder color shown in the swatch when
     *  the user has not picked an override. */
    fallbacks?: Record<string, string>;
    headingId?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: Record<string, string>): void;
}>();

function setVar(name: string, value: string) {
    const next = { ...props.modelValue };

    if (value === '') {
        delete next[name];
    } else {
        next[name] = value;
    }

    emit('update:modelValue', next);
}

function valueOf(name: string): string {
    return props.modelValue[name] ?? '';
}

function fallbackOf(name: string): string | undefined {
    return props.fallbacks?.[name];
}

function groupVariables(group: PaletteVariableGroup) {
    return group.variables;
}
</script>

<template>
    <div class="space-y-6" :aria-labelledby="headingId">
        <div v-for="group in schema" :key="group.group" class="space-y-3">
            <div
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                {{ group.label }}
            </div>
            <div class="grid gap-3">
                <div
                    v-for="v in groupVariables(group)"
                    :key="v.name"
                    class="grid gap-1.5"
                >
                    <Label
                        :for="`${headingId}-${v.name}`"
                        class="text-xs font-normal"
                    >
                        {{ v.label }}
                        <code
                            class="ml-1 text-[10px] text-muted-foreground/70"
                            >{{ v.name }}</code
                        >
                    </Label>
                    <ColorPickerInput
                        :id="`${headingId}-${v.name}`"
                        :model-value="valueOf(v.name)"
                        :label="v.label"
                        :fallback="fallbackOf(v.name)"
                        @update:model-value="(val) => setVar(v.name, val)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
