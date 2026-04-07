<script setup lang="ts">
defineProps<{ show: boolean }>();
const emit = defineEmits<{ done: [] }>();

function onAnimationEnd(event: AnimationEvent) {
    if (event.animationName.includes('mail-fly')) {
        emit('done');
    }
}
</script>

<template>
    <div v-if="show" class="pointer-events-none mt-2 flex justify-center" aria-hidden="true">
        <svg
            class="mail-letter size-10 text-primary"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            @animationend="onAnimationEnd"
        >
            <rect x="2" y="4" width="20" height="16" rx="2" />
            <path d="m22 7-10 5L2 7" />
        </svg>
    </div>
</template>

<style scoped>
.mail-letter {
    animation: mail-fly 1.4s ease-in forwards;
}

@keyframes mail-fly {
    0% {
        transform: translate(0, 0) rotate(0deg) scale(1);
        opacity: 1;
    }
    25% {
        transform: translate(0, -10px) rotate(-4deg) scale(1.05);
        opacity: 1;
    }
    100% {
        transform: translate(80px, -120px) rotate(20deg) scale(0.4);
        opacity: 0;
    }
}
</style>
