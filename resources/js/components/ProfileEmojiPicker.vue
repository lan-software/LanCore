<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import EmojiPicker from 'vue3-emoji-picker';
import type { EmojiExt } from 'vue3-emoji-picker';
import 'vue3-emoji-picker/css';

const props = defineProps<{
    modelValue: string | null;
    name: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: string | null): void;
}>();

const isOpen = ref(false);
const wrapper = ref<HTMLDivElement>();

function toggle(): void {
    isOpen.value = !isOpen.value;
}

function close(): void {
    isOpen.value = false;
}

function handleSelect(emoji: EmojiExt): void {
    emit('update:modelValue', emoji.i);
    close();
}

function clear(): void {
    emit('update:modelValue', null);
}

function handleOutside(event: MouseEvent): void {
    if (
        isOpen.value &&
        wrapper.value &&
        !wrapper.value.contains(event.target as Node)
    ) {
        close();
    }
}

onMounted(() => {
    document.addEventListener('mousedown', handleOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleOutside);
});
</script>

<template>
    <div ref="wrapper" class="relative inline-block">
        <input
            type="hidden"
            :name="props.name"
            :value="props.modelValue ?? ''"
        />

        <div class="flex items-center gap-2">
            <button
                type="button"
                class="flex h-10 w-16 items-center justify-center rounded-md border border-input bg-background text-2xl shadow-xs transition-colors hover:bg-muted focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                :aria-expanded="isOpen"
                :aria-label="$t('settings.profile.profileEmoji')"
                @click="toggle"
            >
                <span v-if="props.modelValue">{{ props.modelValue }}</span>
                <span v-else class="text-base text-muted-foreground">
                    {{ props.placeholder ?? '🙂' }}
                </span>
            </button>

            <button
                v-if="props.modelValue"
                type="button"
                class="text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                @click="clear"
            >
                {{ $t('common.remove') }}
            </button>
        </div>

        <div
            v-if="isOpen"
            class="absolute top-12 left-0 z-50 rounded-md border border-border bg-card shadow-lg"
        >
            <EmojiPicker
                :native="true"
                :hide-search="false"
                :disable-skin-tones="true"
                @select="handleSelect"
            />
        </div>
    </div>
</template>
