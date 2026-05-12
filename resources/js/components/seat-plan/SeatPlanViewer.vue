<script setup lang="ts">
import { computed, ref } from 'vue';
import SeatPlanScene from './SeatPlanScene.vue';
import type {
    BoundingBox,
    ColoringStrategy,
    SeatPalette,
    SeatPlanImperativeHandle,
    SeatPlanScenePlan,
    ViewState,
} from './types';

/**
 * Read-only viewer wrapper around SeatPlanScene. Owns its own pan/zoom
 * state and exposes the scene's imperative handle so consumers can drive
 * focus / zoom flows (Welcome focus_user, Picker zoom-to-selection, …).
 *
 * For interaction-heavy surfaces (the editor) consume SeatPlanScene
 * directly so you can manage the view, supply overlays, and intercept
 * raw pointer events without going through this wrapper.
 */

const props = withDefaults(
    defineProps<{
        plan: SeatPlanScenePlan;
        selectedSeatIds?: ReadonlyArray<number | string>;
        palette?: Partial<SeatPalette>;
        coloringStrategy?: ColoringStrategy;
        showLegend?: boolean;
        showTooltip?: boolean;
    }>(),
    {
        selectedSeatIds: () => [],
        palette: () => ({}),
        coloringStrategy: 'block-color',
        showLegend: true,
        showTooltip: true,
    },
);

const emit = defineEmits<{
    'seat-click': [
        payload: { id: number | string; salable: boolean; rect: DOMRect },
    ];
    'seat-hover-enter': [payload: { id: number | string; rect: DOMRect }];
    'seat-hover-leave': [];
    'view-change': [view: ViewState];
    ready: [];
}>();

const sceneRef = ref<InstanceType<typeof SeatPlanScene> | null>(null);

/* Forward the scene's imperative API one-to-one. */
defineExpose<SeatPlanImperativeHandle>({
    fitToVenue(options): void {
        sceneRef.value?.fitToVenue(options);
    },
    zoomToBlock(blockId, options): void {
        sceneRef.value?.zoomToBlock(blockId, options);
    },
    zoomToSeat(seatId, options): void {
        sceneRef.value?.zoomToSeat(seatId, options);
    },
    zoomToBoundingBox(bbox: BoundingBox, options): void {
        sceneRef.value?.zoomToBoundingBox(bbox, options);
    },
    pulseSeat(seatId, options): void {
        sceneRef.value?.pulseSeat(seatId, options);
    },
    getSeatScreenRect(seatId): DOMRect | null {
        return sceneRef.value?.getSeatScreenRect(seatId) ?? null;
    },
    getView(): ViewState {
        return (
            sceneRef.value?.getView() ?? { panX: 0, panY: 0, zoom: 1 }
        );
    },
    setView(view, options): void {
        sceneRef.value?.setView(view, options);
    },
});

const passthroughProps = computed(() => ({
    plan: props.plan,
    selectedSeatIds: props.selectedSeatIds,
    palette: props.palette,
    coloringStrategy: props.coloringStrategy,
    showLegend: props.showLegend,
    showTooltip: props.showTooltip,
}));
</script>

<template>
    <SeatPlanScene
        ref="sceneRef"
        v-bind="passthroughProps"
        @seat-click="(p) => emit('seat-click', p)"
        @seat-hover-enter="(p) => emit('seat-hover-enter', p)"
        @seat-hover-leave="emit('seat-hover-leave')"
        @view-change="(v) => emit('view-change', v)"
        @ready="emit('ready')"
    />
</template>
