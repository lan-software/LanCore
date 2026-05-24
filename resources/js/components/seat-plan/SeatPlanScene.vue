<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import { useI18n } from 'vue-i18n';
import type { SeatPlanBlock, SeatPlanSeat } from '@/types/domain';
import { tweenView } from './animation';
import type { Cancel } from './animation';
import { blockBoundingBox, fitViewToBbox, planBoundingBox } from './geometry';
import { DEFAULT_PALETTE } from './types';
import type {
    BoundingBox,
    ColoringStrategy,
    SeatPalette,
    SeatPlanScenePlan,
    ViewState,
} from './types';

/**
 * Shared, first-party SVG renderer for seat plans. Used by both the read-only
 * SeatPlanViewer (Picker / Welcome / Edit-preview) and the editor wrapper.
 *
 * The scene owns:
 *   - SVG element + 1600×1000 viewBox
 *   - World <g> transform driven by `view` (pan/zoom)
 *   - Background image, grid, blocks, seats, labels, legend, native tooltip
 *   - Wheel/pointer pan & zoom (when `interactive`)
 *   - Hit testing for seat & scene events
 *   - Imperative API: fitToVenue, zoomToBlock/Seat/BoundingBox, pulseSeat,
 *     getSeatScreenRect, getView/setView
 *
 * It does NOT own:
 *   - Selection mutation (consumers pass `selectedSeatIds`)
 *   - Click / hover semantics (it emits raw events; consumers decide meaning)
 *   - Editor concerns (marquee, drag) — those go in the `overlay` slot
 */

const props = withDefaults(
    defineProps<{
        plan: SeatPlanScenePlan;
        view?: ViewState;
        interactive?: boolean;
        selectedSeatIds?: ReadonlyArray<number | string>;
        palette?: Partial<SeatPalette>;
        coloringStrategy?: ColoringStrategy;
        showGrid?: boolean;
        gridSize?: number;
        showLegend?: boolean;
        showTooltip?: boolean;
        /**
         * Optional override fill for the block-title watermark. Defaults to a
         * theme-aware gray applied via CSS.
         */
        cursor?: string;
    }>(),
    {
        view: undefined,
        interactive: true,
        selectedSeatIds: () => [],
        palette: () => ({}),
        coloringStrategy: 'block-color',
        showGrid: false,
        gridSize: 15,
        showLegend: false,
        showTooltip: false,
        cursor: undefined,
    },
);

const emit = defineEmits<{
    'seat-click': [
        payload: { id: string | string; salable: boolean; rect: DOMRect },
    ];
    'seat-pointerdown': [
        event: PointerEvent,
        payload: { id: string | string; blockId: string | string },
    ];
    'seat-hover-enter': [payload: { id: string | string; rect: DOMRect }];
    'seat-hover-leave': [];
    'scene-pointerdown': [event: PointerEvent];
    'view-change': [view: ViewState];
    ready: [];
}>();

const { t } = useI18n();

const palette = computed<SeatPalette>(() => ({
    ...DEFAULT_PALETTE,
    ...props.palette,
}));

/* The internal view always exists. When the consumer passes `props.view`,
 * we mirror it; otherwise the scene owns its own state. Two-way sync via
 * watchers keeps both in step without forcing v-model. */
const internalView = reactive<ViewState>({
    panX: props.view?.panX ?? 0,
    panY: props.view?.panY ?? 0,
    zoom: props.view?.zoom ?? 1,
});

watch(
    () => props.view,
    (incoming) => {
        if (!incoming) {
            return;
        }

        if (
            incoming.panX !== internalView.panX ||
            incoming.panY !== internalView.panY ||
            incoming.zoom !== internalView.zoom
        ) {
            internalView.panX = incoming.panX;
            internalView.panY = incoming.panY;
            internalView.zoom = incoming.zoom;
        }
    },
    { deep: true },
);

const svgRef = ref<SVGSVGElement | null>(null);

/**
 * World-space transform applied as a single `transform` attribute on the
 * wrapping <g>. One DOM write per frame, GPU-composited, no per-child
 * relayout.
 *
 * Mapping: world point (panX, panY) is centered at SVG (800, 500) inside
 * the fixed 1600×1000 viewBox.
 *   svg = (world - pan) * zoom + viewCenter
 *   world = (svg - viewCenter) / zoom + pan
 */
const worldTransform = computed(
    () =>
        `translate(${800 - internalView.panX * internalView.zoom} ${500 - internalView.panY * internalView.zoom}) scale(${internalView.zoom})`,
);

const MAX_ZOOM = 5;
const ABSOLUTE_MIN_ZOOM = 0.05;

/**
 * Lower zoom bound derived from the plan's content extent: the zoom at which
 * the full plan just fits the viewport (with the same 1.2 padding factor used
 * by `fitToVenue`). Capped at 1× from above so tiny plans can still be viewed
 * at 100 %, and floored at {@link ABSOLUTE_MIN_ZOOM} so degenerate plans
 * (one seat, no labels) don't permit unbounded zoom-out.
 *
 * Reactive on `props.plan`, so growing the plan in the editor relaxes the
 * limit and shrinking it tightens it back.
 */
const minZoom = computed<number>(() => {
    const bbox = planBoundingBox(props.plan);

    if (!bbox) {
        return ABSOLUTE_MIN_ZOOM;
    }

    const fit = fitViewToBbox(bbox, {
        padding: 1.2,
        minZoom: ABSOLUTE_MIN_ZOOM,
        maxZoom: MAX_ZOOM,
    });

    return Math.max(ABSOLUTE_MIN_ZOOM, Math.min(fit.zoom, 1));
});

function clampZoom(value: number): number {
    return Math.min(Math.max(value, minZoom.value), MAX_ZOOM);
}

const selectedSet = computed<Set<string>>(
    () => new Set(props.selectedSeatIds.map((id) => String(id))),
);

function isSelected(seat: SeatPlanSeat): boolean {
    return selectedSet.value.has(String(seat.id));
}

function seatFill(block: SeatPlanBlock, seat: SeatPlanSeat): string {
    if (isSelected(seat)) {
        return palette.value.selected;
    }

    if (!seat.salable) {
        return palette.value.notSalable;
    }

    if (typeof props.coloringStrategy === 'function') {
        return props.coloringStrategy(seat, block);
    }

    if (seat.color) {
        return seat.color;
    }

    return block.color || palette.value.blockFallback;
}

function seatDisplayTitle(block: SeatPlanBlock, seat: SeatPlanSeat): string {
    return (block.seat_title_prefix ?? '') + seat.title;
}

/** Screen → SVG-viewBox point (0..1600 × 0..1000). */
function svgPointRaw(event: PointerEvent | WheelEvent): {
    x: number;
    y: number;
} {
    if (!svgRef.value) {
        return { x: 0, y: 0 };
    }

    const pt = svgRef.value.createSVGPoint();
    pt.x = event.clientX;
    pt.y = event.clientY;
    const ctm = svgRef.value.getScreenCTM();

    if (!ctm) {
        return { x: 0, y: 0 };
    }

    const p = pt.matrixTransform(ctm.inverse());

    return { x: p.x, y: p.y };
}

/** Screen → world-space point via the inverse of `worldTransform`. */
function svgPointToWorld(event: PointerEvent | WheelEvent): {
    x: number;
    y: number;
} {
    const svg = svgPointRaw(event);

    return {
        x: (svg.x - 800) / internalView.zoom + internalView.panX,
        y: (svg.y - 500) / internalView.zoom + internalView.panY,
    };
}

function emitViewChange(): void {
    emit('view-change', { ...internalView });
}

function applyView(next: ViewState): void {
    internalView.panX = next.panX;
    internalView.panY = next.panY;
    internalView.zoom = next.zoom;
    emitViewChange();
}

let activeTween: Cancel | null = null;

function cancelTween(): void {
    if (activeTween) {
        activeTween();
        activeTween = null;
    }
}

function setViewAnimated(next: ViewState, animated: boolean): void {
    cancelTween();

    if (!animated) {
        applyView(next);

        return;
    }

    const from: ViewState = { ...internalView };
    activeTween = tweenView(from, next, 500, (frame) => {
        applyView(frame);
    });
}

/* --- Pan & zoom (only active when `props.interactive`) --- */

const panning = ref(false);

function startPan(event: PointerEvent): void {
    panning.value = true;
    /* CRITICAL: track cursor in raw SVG viewBox coords (independent of pan)
     * rather than in world coords. world coords feed back through
     * `svgPointToWorld` → `internalView.panX/Y`, so each rAF flush moves the
     * world reference frame and the next pointermove under-corrects against
     * the new frame. The result is per-frame oscillation that reads as jitter.
     * Raw SVG coords + a captured `panZoom` snapshot keep the math frame-stable.
     */
    const startSvg = svgPointRaw(event);
    const startPanX = internalView.panX;
    const startPanY = internalView.panY;
    const panZoom = internalView.zoom;
    (event.target as Element).setPointerCapture?.(event.pointerId);

    let pendingX = startPanX;
    let pendingY = startPanY;
    let rafId: string | null = null;

    function flush(): void {
        rafId = null;
        internalView.panX = pendingX;
        internalView.panY = pendingY;
        emitViewChange();
    }

    function onMove(ev: PointerEvent): void {
        const curSvg = svgPointRaw(ev);
        pendingX = startPanX - (curSvg.x - startSvg.x) / panZoom;
        pendingY = startPanY - (curSvg.y - startSvg.y) / panZoom;

        if (rafId === null) {
            rafId = requestAnimationFrame(flush);
        }
    }

    function onUp(): void {
        try {
            (event.target as Element).releasePointerCapture?.(event.pointerId);
        } catch {
            /* already released */
        }

        document.removeEventListener('pointermove', onMove);
        document.removeEventListener('pointerup', onUp);

        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            flush();
        }

        panning.value = false;
    }

    document.addEventListener('pointermove', onMove);
    document.addEventListener('pointerup', onUp);
}

function onScenePointerDown(event: PointerEvent): void {
    if (!props.interactive) {
        emit('scene-pointerdown', event);

        return;
    }

    /* Middle/right mouse → pan; left → forward to consumer. */
    if (event.button === 1 || event.button === 2) {
        startPan(event);

        return;
    }

    emit('scene-pointerdown', event);
}

function onWheel(event: WheelEvent): void {
    if (!props.interactive) {
        return;
    }

    event.preventDefault();
    cancelTween();

    const { x: cx, y: cy } = svgPointToWorld(event);
    const k = event.deltaY > 0 ? 0.9 : 1.1;
    const oldZoom = internalView.zoom;
    const newZoom = clampZoom(oldZoom * k);
    const actualK = newZoom / oldZoom;

    if (actualK === 1) {
        return;
    }

    internalView.zoom = newZoom;
    internalView.panX = cx + (internalView.panX - cx) / actualK;
    internalView.panY = cy + (internalView.panY - cy) / actualK;
    emitViewChange();
}

/* --- Seat events --- */

function onSeatPointerDown(
    event: PointerEvent,
    block: SeatPlanBlock,
    seat: SeatPlanSeat,
): void {
    emit('seat-pointerdown', event, { id: seat.id, blockId: block.id });
}

function onSeatClick(
    event: PointerEvent,
    block: SeatPlanBlock,
    seat: SeatPlanSeat,
): void {
    /* Only swallow the event if the consumer is genuinely picking — emit
     * regardless. The consumer decides what to do based on seat.salable. */
    event.stopPropagation();
    void block;
    const target = event.currentTarget as SVGGElement;
    const rect = target.getBoundingClientRect();
    emit('seat-click', { id: seat.id, salable: seat.salable, rect });
}

/* --- Native tooltip --- */

const tooltipState = ref<{
    label: string;
    rect: DOMRect;
} | null>(null);

function onSeatPointerEnter(
    event: PointerEvent,
    block: SeatPlanBlock,
    seat: SeatPlanSeat,
): void {
    const target = event.currentTarget as SVGGElement;
    const rect = target.getBoundingClientRect();
    emit('seat-hover-enter', { id: seat.id, rect });

    if (props.showTooltip && seat.salable) {
        tooltipState.value = {
            label: seatDisplayTitle(block, seat),
            rect,
        };
    } else {
        tooltipState.value = null;
    }
}

function onSeatPointerLeave(): void {
    emit('seat-hover-leave');
    tooltipState.value = null;
}

const tooltipStyle = computed<Record<string, string>>(() => {
    if (!tooltipState.value) {
        return { display: 'none' };
    }

    const rect = tooltipState.value.rect;

    return {
        position: 'fixed',
        top: `${Math.round(rect.top - 32)}px`,
        left: `${Math.round(rect.left + rect.width / 2)}px`,
        transform: 'translate(-50%, 0)',
        pointerEvents: 'none',
    };
});

/* --- Pulse animation --- */

const pulses = reactive<Map<string, { id: string; expiresAt: number }>>(
    new Map(),
);
let pulseSweepHandle: number | null = null;

function startPulseSweep(): void {
    if (pulseSweepHandle !== null) {
        return;
    }

    function sweep(): void {
        const now = performance.now();
        let removed = false;

        for (const [key, pulse] of pulses) {
            if (pulse.expiresAt <= now) {
                pulses.delete(key);
                removed = true;
            }
        }

        if (removed) {
            /* Vue picks the change up via the reactive Map. */
        }

        if (pulses.size > 0) {
            pulseSweepHandle = requestAnimationFrame(sweep);
        } else {
            pulseSweepHandle = null;
        }
    }

    pulseSweepHandle = requestAnimationFrame(sweep);
}

function pulseSeat(
    seatId: string | string,
    options: { durationMs?: number } = {},
): void {
    const durationMs = options.durationMs ?? 1500;
    const key = String(seatId);
    pulses.set(key, { id: key, expiresAt: performance.now() + durationMs });
    startPulseSweep();
}

function isPulsing(seat: SeatPlanSeat): boolean {
    return pulses.has(String(seat.id));
}

/* --- Imperative API --- */

function findSeatBlock(
    seatId: string | string,
): { block: SeatPlanBlock; seat: SeatPlanSeat } | null {
    const idStr = String(seatId);

    for (const block of props.plan.blocks ?? []) {
        for (const seat of block.seats) {
            if (String(seat.id) === idStr) {
                return { block, seat };
            }
        }
    }

    return null;
}

function fitToVenue({
    animated = true,
    padding = 1.2,
}: { animated?: boolean; padding?: number } = {}): void {
    const bbox = planBoundingBox(props.plan);

    if (!bbox) {
        setViewAnimated({ panX: 0, panY: 0, zoom: 1 }, animated);

        return;
    }

    const next = fitViewToBbox(bbox, { padding });
    setViewAnimated(next, animated);
}

function zoomToBoundingBox(
    bbox: BoundingBox,
    {
        animated = true,
        padding = 1.2,
    }: { animated?: boolean; padding?: number } = {},
): void {
    const next = fitViewToBbox(bbox, { padding });
    setViewAnimated(next, animated);
}

function zoomToBlock(
    blockId: string | string,
    options: { animated?: boolean; padding?: number } = {},
): void {
    const idStr = String(blockId);
    const block = (props.plan.blocks ?? []).find((b) => String(b.id) === idStr);

    if (!block) {
        return;
    }

    const bbox = blockBoundingBox(block);

    if (!bbox) {
        return;
    }

    zoomToBoundingBox(bbox, options);
}

function zoomToSeat(
    seatId: string | string,
    {
        animated = true,
        padding = 80,
    }: { animated?: boolean; padding?: number } = {},
): void {
    const found = findSeatBlock(seatId);

    if (!found) {
        return;
    }

    const { seat } = found;
    /* Padding is in world units around the seat centre. */
    zoomToBoundingBox(
        {
            minX: seat.x - padding,
            maxX: seat.x + padding,
            minY: seat.y - padding,
            maxY: seat.y + padding,
        },
        { animated, padding: 1 },
    );
}

function getSeatScreenRect(seatId: string | string): DOMRect | null {
    const root = svgRef.value;

    if (!root) {
        return null;
    }

    const node = root.querySelector<SVGGElement>(
        `g[data-seat-id="${CSS.escape(String(seatId))}"]`,
    );

    return node?.getBoundingClientRect() ?? null;
}

function getView(): ViewState {
    return { ...internalView };
}

function setView(
    next: Partial<ViewState>,
    options: { animated?: boolean } = {},
): void {
    setViewAnimated(
        {
            panX: next.panX ?? internalView.panX,
            panY: next.panY ?? internalView.panY,
            zoom: clampZoom(next.zoom ?? internalView.zoom),
        },
        options.animated ?? false,
    );
}

defineExpose({
    fitToVenue,
    zoomToBlock,
    zoomToSeat,
    zoomToBoundingBox,
    pulseSeat,
    getSeatScreenRect,
    getView,
    setView,
});

/* --- Lifecycle / ready --- */

onMounted(() => {
    /* Auto-fit on first mount unless the consumer already supplied a view. */
    if (!props.view) {
        fitToVenue({ animated: false });
    }

    nextTick(() => {
        emit('ready');
    });
});

onBeforeUnmount(() => {
    cancelTween();

    if (pulseSweepHandle !== null) {
        cancelAnimationFrame(pulseSweepHandle);
        pulseSweepHandle = null;
    }
});

/* Re-emit `ready` after a deep plan replacement so consumers (Picker,
 * Welcome) can re-apply selection / focus highlights. */
watch(
    () => props.plan,
    () => {
        nextTick(() => {
            emit('ready');
        });
    },
);

const legendItems = computed(() => [
    {
        key: 'available',
        color: palette.value.salable,
        label: t('seating.viewer.legend.available'),
    },
    {
        key: 'taken',
        color: palette.value.notSalable,
        label: t('seating.viewer.legend.taken'),
    },
    {
        key: 'selected',
        color: palette.value.selected,
        label: t('seating.viewer.legend.selected'),
    },
]);
</script>

<template>
    <div class="seat-plan-scene">
        <svg
            ref="svgRef"
            class="seat-plan-scene__svg"
            :class="{ 'cursor-grabbing': panning }"
            :style="cursor ? { cursor } : undefined"
            viewBox="0 0 1600 1000"
            preserveAspectRatio="xMidYMid meet"
            @pointerdown="onScenePointerDown"
            @wheel.prevent="onWheel"
            @contextmenu.prevent
        >
            <defs>
                <pattern
                    id="seat-plan-scene-grid"
                    :width="gridSize"
                    :height="gridSize"
                    patternUnits="userSpaceOnUse"
                >
                    <path
                        :d="`M ${gridSize} 0 L 0 0 0 ${gridSize}`"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="0.3"
                        opacity="0.08"
                    />
                </pattern>
            </defs>

            <g :transform="worldTransform">
                <image
                    v-if="plan.background_image_url"
                    :href="(plan.background_image_url as string) ?? ''"
                    x="-10000"
                    y="-10000"
                    width="20000"
                    height="20000"
                    preserveAspectRatio="xMidYMid slice"
                    opacity="0.6"
                />

                <rect
                    v-if="showGrid"
                    x="-10000"
                    y="-10000"
                    width="20000"
                    height="20000"
                    fill="url(#seat-plan-scene-grid)"
                    pointer-events="none"
                />

                <g class="seat-plan-scene__plan-labels">
                    <g
                        v-for="label in plan.labels ?? []"
                        :key="'pl' + String(label.id ?? label.title)"
                        :transform="`translate(${label.x ?? 0}, ${label.y ?? 0})`"
                        class="seat-plan-scene__label"
                        :data-label-id="String(label.id ?? '')"
                    >
                        <text
                            text-anchor="middle"
                            dominant-baseline="central"
                            font-size="10"
                            font-weight="bold"
                            class="seat-plan-scene__label-text"
                        >
                            {{ label.title }}
                        </text>
                    </g>
                </g>

                <g
                    v-for="block in plan.blocks"
                    :key="'b' + String(block.id)"
                    class="seat-plan-scene__block"
                    :data-block-id="String(block.id)"
                >
                    <image
                        v-if="block.background_image_url"
                        :href="block.background_image_url ?? ''"
                        x="-1500"
                        y="-1500"
                        width="3000"
                        height="3000"
                        preserveAspectRatio="xMidYMid slice"
                        opacity="0.4"
                    />
                    <g
                        v-for="(seat, seatIndex) in block.seats"
                        :key="'s' + String(seat.id)"
                        :transform="`translate(${seat.x ?? 0}, ${seat.y ?? 0})`"
                        :class="[
                            'seat-plan-scene__seat',
                            {
                                'seat-plan-scene__seat--selected':
                                    isSelected(seat),
                                'seat-plan-scene__seat--not-salable':
                                    !seat.salable,
                                'seat-plan-scene__seat--pulsing':
                                    isPulsing(seat),
                            },
                        ]"
                        :data-seat-id="String(seat.id)"
                        :data-block-id="String(block.id)"
                        :data-seat-index="seatIndex"
                        @pointerdown="
                            (e: PointerEvent) =>
                                onSeatPointerDown(e, block, seat)
                        "
                        @click="
                            (e: PointerEvent | MouseEvent) =>
                                onSeatClick(e as PointerEvent, block, seat)
                        "
                        @pointerenter="
                            (e: PointerEvent) =>
                                onSeatPointerEnter(e, block, seat)
                        "
                        @pointerleave="onSeatPointerLeave"
                    >
                        <circle
                            class="seat-plan-scene__seat-circle"
                            r="10"
                            :fill="seatFill(block, seat)"
                            stroke="rgba(0,0,0,0.2)"
                            stroke-width="0.5"
                        />
                        <circle
                            v-if="isSelected(seat)"
                            r="12"
                            fill="none"
                            class="seat-plan-scene__seat-selected-ring"
                            :stroke="palette.selected"
                            stroke-width="1.8"
                        />
                        <circle
                            v-if="isPulsing(seat)"
                            class="seat-plan-scene__seat-pulse"
                            r="12"
                            fill="none"
                            stroke="#f59e0b"
                            stroke-width="3"
                            pointer-events="none"
                        />
                        <text
                            text-anchor="middle"
                            dominant-baseline="central"
                            font-size="6"
                            fill="white"
                            pointer-events="none"
                        >
                            {{ seatDisplayTitle(block, seat) }}
                        </text>
                    </g>

                    <g
                        v-for="label in block.labels"
                        :key="'l' + String(label.id ?? label.title)"
                        :transform="`translate(${label.x ?? 0}, ${label.y ?? 0})`"
                        class="seat-plan-scene__label"
                        :data-label-id="String(label.id ?? '')"
                    >
                        <text
                            text-anchor="middle"
                            dominant-baseline="central"
                            font-size="10"
                            font-weight="bold"
                            class="seat-plan-scene__label-text"
                        >
                            {{ label.title }}
                        </text>
                    </g>
                </g>

                <slot name="overlay" />
            </g>
        </svg>

        <!-- Floating overlays (HTML, sibling of SVG) -->
        <ul v-if="showLegend" class="seat-plan-scene__legend">
            <li
                v-for="item in legendItems"
                :key="item.key"
                class="seat-plan-scene__legend-item"
            >
                <span
                    class="seat-plan-scene__legend-swatch"
                    :style="{ backgroundColor: item.color }"
                />
                <span>{{ item.label }}</span>
            </li>
        </ul>

        <Teleport to="body">
            <div
                v-if="tooltipState"
                class="seat-plan-scene__tooltip"
                :style="tooltipStyle"
            >
                {{ tooltipState.label }}
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.seat-plan-scene {
    position: relative;
    width: 100%;
    height: 100%;
}

.seat-plan-scene__svg {
    width: 100%;
    height: 100%;
    touch-action: none;
    user-select: none;
    background: var(--background, transparent);
}

.seat-plan-scene__seat {
    cursor: pointer;
}

.seat-plan-scene__seat--not-salable {
    cursor: default;
}

.seat-plan-scene__seat-pulse {
    transform-box: fill-box;
    transform-origin: center;
    animation: seat-plan-scene-pulse 1500ms ease-out infinite;
}

@keyframes seat-plan-scene-pulse {
    0% {
        transform: scale(0.9);
        opacity: 1;
    }

    100% {
        transform: scale(1.8);
        opacity: 0;
    }
}

.seat-plan-scene__legend {
    position: absolute;
    right: 12px;
    bottom: 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin: 0;
    padding: 8px 10px;
    border-radius: 8px;
    list-style: none;
    background: rgba(255, 255, 255, 0.85);
    color: #111827;
    font-size: 11px;
    line-height: 1.2;
    pointer-events: none;
    backdrop-filter: blur(4px);
}

:global(html.dark) .seat-plan-scene__legend {
    background: rgba(17, 24, 39, 0.85);
    color: #f3f4f6;
}

.seat-plan-scene__legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.seat-plan-scene__legend-swatch {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1px solid rgba(0, 0, 0, 0.15);
}

.seat-plan-scene__tooltip {
    z-index: 60;
    padding: 4px 8px;
    border-radius: 6px;
    background: rgba(17, 24, 39, 0.92);
    color: #f9fafb;
    font-size: 11px;
    line-height: 1.2;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}

.seat-plan-scene__label-text {
    fill: rgb(31, 41, 55);
}

:global(html.dark) .seat-plan-scene__label-text {
    fill: rgb(229, 231, 235);
}
</style>
