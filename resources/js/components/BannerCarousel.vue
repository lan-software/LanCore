<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'

const props = defineProps<{
    images: string[]
    alt?: string
    intervalMs?: number
    class?: string
}>()

const currentIndex = ref(0)
let timer: ReturnType<typeof setInterval> | null = null

function next() {
    currentIndex.value = (currentIndex.value + 1) % props.images.length
}

function goTo(index: number) {
    currentIndex.value = index
    restartTimer()
}

function restartTimer() {
    if (timer !== null) {
        clearInterval(timer)
    }
    if (props.images.length > 1) {
        timer = setInterval(next, props.intervalMs ?? 4000)
    }
}

onMounted(() => {
    restartTimer()
})

onUnmounted(() => {
    if (timer !== null) {
        clearInterval(timer)
    }
})
</script>

<template>
    <div
        v-if="images.length > 0"
        class="relative overflow-hidden rounded-xl border"
        :class="props.class"
    >
        <!-- Images -->
        <template
            v-for="(src, index) in images"
            :key="src"
        >
            <img
                :src="src"
                :alt="alt"
                class="w-full object-cover transition-opacity duration-700"
                :class="index === currentIndex ? 'opacity-100' : 'pointer-events-none absolute inset-0 h-full opacity-0'"
            />
        </template>

        <!-- Dot indicators (only when more than one image) -->
        <div
            v-if="images.length > 1"
            class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5"
        >
            <button
                v-for="(_, index) in images"
                :key="index"
                type="button"
                class="size-2 rounded-full transition-colors"
                :class="index === currentIndex ? 'bg-white' : 'bg-white/40 hover:bg-white/70'"
                :aria-label="`Go to image ${index + 1}`"
                @click="goTo(index)"
            />
        </div>
    </div>
</template>
