<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'

const props = defineProps<{
    images: string[]
    alt?: string
    intervalMs?: number
    class?: string
}>()

const currentIndex = ref(0)
let timer: ReturnType<typeof setTimeout> | null = null

const interval = computed(() => props.intervalMs ?? 7000)

function advance() {
    currentIndex.value = (currentIndex.value + 1) % props.images.length
    scheduleNext()
}

function goTo(index: number) {
    if (index === currentIndex.value) {
        return
    }

    currentIndex.value = index
    scheduleNext()
}

function scheduleNext() {
    if (timer !== null) {
        clearTimeout(timer)
    }

    if (props.images.length > 1) {
        timer = setTimeout(advance, interval.value)
    }
}

onMounted(scheduleNext)

onUnmounted(() => {
    if (timer !== null) {
        clearTimeout(timer)
    }
})
</script>

<template>
    <div
        v-if="images.length > 0"
        class="relative overflow-hidden rounded-xl border bg-muted aspect-[3/1]"
        :class="props.class"
    >
        <!-- All images are absolutely stacked — no layout shift on transition -->
        <img
            v-for="(src, index) in images"
            :key="src"
            :src="src"
            :alt="alt"
            class="absolute inset-0 h-full w-full object-cover transition-opacity duration-1000 ease-in-out"
            :class="index === currentIndex ? 'opacity-100 z-10' : 'opacity-0 z-0'"
        />

        <!-- Dot indicators (only when more than one image) -->
        <div
            v-if="images.length > 1"
            class="absolute bottom-3 left-1/2 z-20 flex -translate-x-1/2 gap-2"
        >
            <button
                v-for="(_, index) in images"
                :key="index"
                type="button"
                class="rounded-full transition-all duration-300"
                :class="index === currentIndex ? 'size-2.5 bg-white' : 'size-2 bg-white/40 hover:bg-white/70'"
                :aria-label="`Go to image ${index + 1}`"
                @click="goTo(index)"
            />
        </div>
    </div>
</template>
