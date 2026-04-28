<script setup lang="ts">
withDefaults(defineProps<{ size?: number | string }>(), { size: 16 });
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="currentColor"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <path
            d="M12 0C5.6 0 .4 4.9.0 11.2l6.4 2.7a3.4 3.4 0 0 1 1.9-.6h.2l2.9-4.2v-.1a4.5 4.5 0 1 1 4.5 4.5h-.1l-4.2 3v.2a3.4 3.4 0 0 1-6.6 1.2L0 16a12 12 0 1 0 12-16Zm-4.4 18.2-1.5-.6a2.6 2.6 0 1 0 1.4-3.4l1.6.7a1.9 1.9 0 1 1-1.5 3.3Zm12.5-7.7a3 3 0 1 0-3 3 3 3 0 0 0 3-3Zm-5.3 0a2.3 2.3 0 1 1 2.3 2.2 2.3 2.3 0 0 1-2.3-2.2Z"
        />
    </svg>
</template>
