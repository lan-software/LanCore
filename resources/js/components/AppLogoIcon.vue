<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import logoIcon from '../../images/logo-icon.png';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        :class="className"
        :src="logoIcon"
        alt="App Logo Icon"
    />
</template>
