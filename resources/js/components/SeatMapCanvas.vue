<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import type { SeatPlanBlock } from '@/types/domain'

const props = withDefaults(
    defineProps<{
        blocks: SeatPlanBlock[]
        options?: Record<string, unknown>
    }>(),
    {
        options: () => ({}),
    },
)

defineEmits<{
    'seat-click': [seat: unknown]
}>()

const containerRef = ref<HTMLDivElement | null>(null)
let seatmapInstance: InstanceType<typeof import('@alisaitteke/seatmap-canvas').SeatMapCanvas> | null = null

async function initSeatmap(): Promise<void> {
    if (!containerRef.value || props.blocks.length === 0) return

    destroySeatmap()

    const { SeatMapCanvas } = await import('@alisaitteke/seatmap-canvas')

    const defaultOptions = {
        legend: true,
        style: {
            seat: {
                radius: 12,
                color: '#6796ff',
                hover: '#5671ff',
                selected: '#56aa45',
                not_salable: '#424747',
            },
            block: {
                fill: '#e2e2e2',
                stroke: '#e2e2e2',
            },
            label: {
                color: '#000',
                radius: 12,
                fontSize: '12px',
                background: '#ffffff',
            },
        },
    }

    const mergedOptions = {
        ...defaultOptions,
        ...props.options,
        style: {
            ...defaultOptions.style,
            ...(props.options?.style as Record<string, unknown> ?? {}),
        },
    }

    seatmapInstance = new SeatMapCanvas(containerRef.value, mergedOptions)
    seatmapInstance.data.addBulkBlock(props.blocks)
}

function destroySeatmap(): void {
    if (seatmapInstance && containerRef.value) {
        containerRef.value.innerHTML = ''
        seatmapInstance = null
    }
}

onMounted(() => {
    initSeatmap()
})

onBeforeUnmount(() => {
    destroySeatmap()
})

watch(
    () => props.blocks,
    () => {
        initSeatmap()
    },
)
</script>

<template>
    <div ref="containerRef" class="seatmap-container" />
</template>

<style>
@import '@alisaitteke/seatmap-canvas/dist/esm/seatmap.canvas.css';

.seatmap-container {
    width: 100%;
    height: 100%;
}

.seatmap-container svg {
    width: 100%;
    height: 100%;
}
</style>
