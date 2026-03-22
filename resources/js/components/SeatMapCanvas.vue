<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import type { SeatPlanBlock, SeatPlanData } from '@/types/domain'

interface ZoneSeat {
    seat_number?: string
    seat_guid: string
    position: { x: number; y: number }
    category: string
}

interface ZoneRow {
    position: { x: number; y: number }
    row_number: string
    row_number_position?: string
    seats: ZoneSeat[]
}

interface Zone {
    name: string
    position: { x: number; y: number }
    rows: ZoneRow[]
}

interface ZoneCategory {
    name: string
    color: string
}

interface ZoneFormat {
    categories?: ZoneCategory[]
    zones: Zone[]
}

const props = withDefaults(
    defineProps<{
        data: SeatPlanData
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

function isZoneFormat(data: SeatPlanData): data is SeatPlanData & ZoneFormat {
    return 'zones' in data && Array.isArray((data as Record<string, unknown>).zones)
}

function hasBlocks(data: SeatPlanData): boolean {
    return 'blocks' in data && Array.isArray(data.blocks) && data.blocks.length > 0
}

/**
 * Convert zones/rows/seats format to native seatmap-canvas blocks format.
 * The library's built-in pretix parser has a bug that accumulates row positions,
 * so we handle the conversion ourselves.
 */
function convertZonesToBlocks(data: ZoneFormat): SeatPlanBlock[] {
    const categoryColors = new Map<string, string>()
    if (data.categories) {
        for (const cat of data.categories) {
            categoryColors.set(cat.name, cat.color)
        }
    }

    const blockMap = new Map<string, SeatPlanBlock>()

    for (const zone of data.zones) {
        const zoneX = zone.position.x
        const zoneY = zone.position.y

        for (const row of zone.rows) {
            const rowX = zoneX + row.position.x
            const rowY = zoneY + row.position.y

            for (const seat of row.seats) {
                const blockId = `${seat.category}-${row.row_number}-${row.row_number_position ?? 'start'}`

                if (!blockMap.has(blockId)) {
                    blockMap.set(blockId, {
                        id: blockId,
                        title: seat.category,
                        color: categoryColors.get(seat.category) ?? '#2c2828',
                        seats: [],
                        labels: [],
                    })
                }

                const block = blockMap.get(blockId)!
                block.seats.push({
                    id: seat.seat_guid,
                    x: rowX + seat.position.x,
                    y: rowY + seat.position.y,
                    title: seat.seat_guid,
                    salable: true,
                })
            }
        }
    }

    const blocks = Array.from(blockMap.values())
    for (const block of blocks) {
        block.seats.sort((a, b) => a.x === b.x ? a.y - b.y : a.x - b.x)
    }

    return blocks
}

function getBlocks(): SeatPlanBlock[] {
    if (isZoneFormat(props.data)) {
        return convertZonesToBlocks(props.data)
    }
    return props.data.blocks ?? []
}

async function initSeatmap(): Promise<void> {
    if (!containerRef.value) return

    const blocks = getBlocks()
    if (blocks.length === 0) return

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
    seatmapInstance.data.replaceData(blocks as unknown as Record<string, unknown>[])
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
    () => props.data,
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
