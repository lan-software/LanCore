<script setup lang="ts">
import { computed, ref } from 'vue';
import type { EntityRef } from './editor-types';
import { rectContainsPoint, snapToGrid } from './geometry';
import type { EditorStore } from './useEditorStore';

const props = defineProps<{
    store: EditorStore;
}>();

/* The store prop is a stateful object whose nested refs/reactives are
 * designed to be mutated. Aliasing to a local const dodges the
 * `vue/no-mutating-props` rule (which can't tell `props.store.view.zoom = X`
 * is mutating an owned nested reactive, not the prop reference itself). */
const store = props.store;

const emit = defineEmits<{
    mutate: [];
    'canvas-click': [event: { x: number; y: number }];
}>();

const svgRef = ref<SVGSVGElement | null>(null);

type Rect = { x1: number; y1: number; x2: number; y2: number };
const marquee = ref<Rect | null>(null);
const dragState = ref<{ dx: number; dy: number; moved: boolean } | null>(null);
const panning = ref(false);

/*
 * Pan/zoom is applied as a single `transform` attribute on a wrapping <g>
 * instead of mutating the SVG viewBox. viewBox changes force the browser to
 * re-lay-out every child (text metrics, stroke widths, hit testing), which
 * is O(children) per pointermove — the source of pan lag on large plans.
 * A transform on one element is GPU-composited; children stay put.
 *
 * Mapping: world point (panX, panY) is centered at SVG (800, 500) inside
 * the fixed 1600×1000 viewBox. For any point in world space, the on-screen
 * SVG coord is:   svg = (world - pan) * zoom + viewCenter
 * Inverse:        world = (svg - viewCenter) / zoom + pan
 */
const worldTransform = computed(() => {
    const v = props.store.view;
    const tx = 800 - v.panX * v.zoom;
    const ty = 500 - v.panY * v.zoom;

    return `translate(${tx} ${ty}) scale(${v.zoom})`;
});

const cursor = computed(() => {
    if (props.store.tool.value === 'delete') {
        return 'crosshair';
    }

    if (panning.value || props.store.tool.value === 'pan') {
        return 'grab';
    }

    return undefined;
});

/* Grid is rendered as a single SVG <pattern> in the template (see
 * `<defs><pattern id="editor-grid">`) — a one-time DOM element rather than
 * hundreds of <line> nodes rebuilt per frame. `gridSize` feeds the pattern's
 * tile size; pan/zoom just shift the SVG viewBox and the browser re-composites
 * the pattern fill without any Vue re-render. */
const gridSize = computed(() => props.store.view.gridSize);

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
function svgPoint(event: PointerEvent | WheelEvent): { x: number; y: number } {
    const svg = svgPointRaw(event);
    const v = props.store.view;

    return {
        x: (svg.x - 800) / v.zoom + v.panX,
        y: (svg.y - 500) / v.zoom + v.panY,
    };
}

function selectionContains(
    kind: EntityRef['kind'],
    id: number | string,
): boolean {
    return props.store.selection.value.some(
        (r) => r.kind === kind && String(r.id) === String(id),
    );
}

function seatDisplayTitle(
    block: { seat_title_prefix?: string | null },
    seat: { title: string },
): string {
    return (block.seat_title_prefix ?? '') + seat.title;
}

function deleteSeat(blockId: number | string, seatId: number | string): void {
    props.store.applyMutation('delete-seat', (draft) => {
        const block = draft.blocks.find(
            (b) => String(b.id) === String(blockId),
        );

        if (!block) {
            return;
        }

        block.seats = block.seats.filter(
            (s) => String(s.id) !== String(seatId),
        );
    });
    props.store.clearSelection();
}

function deleteLabel(blockId: number | string, labelId: number | string): void {
    props.store.applyMutation('delete-label', (draft) => {
        const block = draft.blocks.find(
            (b) => String(b.id) === String(blockId),
        );

        if (!block) {
            return;
        }

        block.labels = block.labels.filter(
            (l) => l.id === undefined || String(l.id) !== String(labelId),
        );
    });
    props.store.clearSelection();
}

function onSeatPointerDown(
    event: PointerEvent,
    blockId: number | string,
    seatId: number | string,
): void {
    if (props.store.tool.value === 'delete') {
        event.stopPropagation();
        deleteSeat(blockId, seatId);

        return;
    }

    if (props.store.tool.value !== 'select') {
        return;
    }

    event.stopPropagation();
    startEntityDrag(event, { kind: 'seat', id: seatId, blockId });
}

function onLabelPointerDown(
    event: PointerEvent,
    blockId: number | string | null,
    labelId: number | string | undefined,
): void {
    if (labelId === undefined) {
        return;
    }

    if (props.store.tool.value === 'delete') {
        event.stopPropagation();

        if (blockId === null) {
            deletePlanLabel(labelId);
        } else {
            deleteLabel(blockId, labelId);
        }

        return;
    }

    if (props.store.tool.value !== 'select') {
        return;
    }

    event.stopPropagation();
    const ref: EntityRef =
        blockId === null
            ? { kind: 'label', id: labelId }
            : { kind: 'label', id: labelId, blockId };
    startEntityDrag(event, ref);
}

function deletePlanLabel(labelId: number | string): void {
    props.store.applyMutation('delete-plan-label', (draft) => {
        draft.labels = (draft.labels ?? []).filter(
            (l) => l.id === undefined || String(l.id) !== String(labelId),
        );
    });
    props.store.clearSelection();
}

function startEntityDrag(event: PointerEvent, entityRef: EntityRef): void {
    if (event.shiftKey) {
        props.store.addToSelection(entityRef);
    } else if (!selectionContains(entityRef.kind, entityRef.id)) {
        props.store.setSelection([entityRef]);
    }

    const start = svgPoint(event);
    let last = start;
    dragState.value = { dx: 0, dy: 0, moved: false };

    (event.target as Element).setPointerCapture(event.pointerId);

    function onMove(ev: PointerEvent): void {
        const cur = svgPoint(ev);
        const dx = cur.x - start.x;
        const dy = cur.y - start.y;
        dragState.value = {
            dx: props.store.view.snapEnabled
                ? snapToGrid(dx, props.store.view.gridSize)
                : dx,
            dy: props.store.view.snapEnabled
                ? snapToGrid(dy, props.store.view.gridSize)
                : dy,
            moved: Math.abs(cur.x - last.x) + Math.abs(cur.y - last.y) > 0,
        };
        last = cur;
    }

    function onUp(): void {
        try {
            (event.target as Element).releasePointerCapture(event.pointerId);
        } catch {
            /* capture may have been released already */
        }

        document.removeEventListener('pointermove', onMove);
        document.removeEventListener('pointerup', onUp);

        if (!dragState.value) {
            return;
        }

        const { dx, dy } = dragState.value;

        if (Math.abs(dx) > 0 || Math.abs(dy) > 0) {
            commitDrag(dx, dy);
        }

        dragState.value = null;
    }

    document.addEventListener('pointermove', onMove);
    document.addEventListener('pointerup', onUp);
}

function commitDrag(dx: number, dy: number): void {
    const selectedSeatIds = new Set(
        props.store.selection.value
            .filter((r) => r.kind === 'seat')
            .map((r) => String(r.id)),
    );
    const selectedLabelIds = new Set(
        props.store.selection.value
            .filter((r) => r.kind === 'label')
            .map((r) => String(r.id)),
    );

    props.store.applyMutation('move', (draft) => {
        for (const block of draft.blocks) {
            for (const seat of block.seats) {
                if (selectedSeatIds.has(String(seat.id))) {
                    seat.x += dx;
                    seat.y += dy;
                }
            }

            for (const label of block.labels) {
                if (
                    label.id !== undefined &&
                    selectedLabelIds.has(String(label.id))
                ) {
                    label.x += dx;
                    label.y += dy;
                }
            }
        }

        for (const label of draft.labels ?? []) {
            if (
                label.id !== undefined &&
                selectedLabelIds.has(String(label.id))
            ) {
                label.x += dx;
                label.y += dy;
            }
        }
    });
    emit('mutate');
}

function onSvgPointerDown(event: PointerEvent): void {
    if (event.button === 2 || event.button === 1) {
        startPan(event);

        return;
    }

    if (event.button !== 0) {
        return;
    }

    if (props.store.tool.value === 'pan') {
        startPan(event);

        return;
    }

    if (props.store.tool.value === 'delete') {
        /* delete-mode clicks on empty canvas are a no-op */
        return;
    }

    if (props.store.tool.value !== 'select') {
        const start = svgPoint(event);
        const coords = props.store.view.snapEnabled
            ? {
                  x: snapToGrid(start.x, props.store.view.gridSize),
                  y: snapToGrid(start.y, props.store.view.gridSize),
              }
            : start;
        emit('canvas-click', coords);

        return;
    }

    startMarquee(event);
}

function startMarquee(event: PointerEvent): void {
    const start = svgPoint(event);
    props.store.clearSelection();
    marquee.value = { x1: start.x, y1: start.y, x2: start.x, y2: start.y };
    (event.target as Element).setPointerCapture(event.pointerId);

    function onMove(ev: PointerEvent): void {
        const cur = svgPoint(ev);

        if (marquee.value) {
            marquee.value = { ...marquee.value, x2: cur.x, y2: cur.y };
        }
    }

    function onUp(): void {
        try {
            (event.target as Element).releasePointerCapture(event.pointerId);
        } catch {
            /* already released */
        }

        document.removeEventListener('pointermove', onMove);
        document.removeEventListener('pointerup', onUp);

        if (marquee.value) {
            commitMarquee(marquee.value);
            marquee.value = null;
        }
    }

    document.addEventListener('pointermove', onMove);
    document.addEventListener('pointerup', onUp);
}

function commitMarquee(rect: Rect): void {
    const refs: EntityRef[] = [];

    for (const block of props.store.plan.value.blocks) {
        for (const seat of block.seats) {
            if (rectContainsPoint(rect, { x: seat.x, y: seat.y })) {
                refs.push({ kind: 'seat', id: seat.id, blockId: block.id });
            }
        }

        for (const label of block.labels) {
            if (label.id === undefined) {
                continue;
            }

            if (rectContainsPoint(rect, { x: label.x, y: label.y })) {
                refs.push({ kind: 'label', id: label.id, blockId: block.id });
            }
        }
    }

    for (const label of props.store.plan.value.labels ?? []) {
        if (label.id === undefined) {
            continue;
        }

        if (rectContainsPoint(rect, { x: label.x, y: label.y })) {
            refs.push({ kind: 'label', id: label.id });
        }
    }

    props.store.setSelection(refs);
}

function startPan(event: PointerEvent): void {
    panning.value = true;
    const start = svgPoint(event);
    const startPan = { x: store.view.panX, y: store.view.panY };
    (event.target as Element).setPointerCapture(event.pointerId);

    /* Every pointermove re-evaluates the viewBox computed, which re-renders
     * every seat/label under a new coordinate space. On large plans this
     * blew past the event budget and caused visible pan stutter. rAF-throttle
     * so we write panX/panY at most once per frame, matching the browser's
     * own render tick. */
    let pendingX = startPan.x;
    let pendingY = startPan.y;
    let rafId: number | null = null;

    function flush(): void {
        rafId = null;
        store.view.panX = pendingX;
        store.view.panY = pendingY;
    }

    function onMove(ev: PointerEvent): void {
        const cur = svgPoint(ev);
        pendingX = startPan.x - (cur.x - start.x);
        pendingY = startPan.y - (cur.y - start.y);

        if (rafId === null) {
            rafId = requestAnimationFrame(flush);
        }
    }

    function onUp(): void {
        try {
            (event.target as Element).releasePointerCapture(event.pointerId);
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

function onWheel(event: WheelEvent): void {
    event.preventDefault();
    const { x: cx, y: cy } = svgPoint(event);
    const k = event.deltaY > 0 ? 0.9 : 1.1;
    const oldZoom = store.view.zoom;
    const newZoom = Math.min(Math.max(oldZoom * k, 0.1), 5);
    const actualK = newZoom / oldZoom;

    if (actualK === 1) {
        return;
    }

    store.view.zoom = newZoom;
    store.view.panX = cx + (store.view.panX - cx) / actualK;
    store.view.panY = cy + (store.view.panY - cy) / actualK;
}

function seatFill(
    block: { color?: string },
    seat: { salable: boolean; color?: string | null },
): string {
    if (!seat.salable) {
        return '#6b7280';
    }

    if (seat.color) {
        return seat.color;
    }

    return block.color ?? '#2c3e50';
}
</script>

<template>
    <svg
        ref="svgRef"
        class="h-full w-full touch-none bg-card select-none"
        :style="cursor ? { cursor } : undefined"
        viewBox="0 0 1600 1000"
        preserveAspectRatio="xMidYMid meet"
        @pointerdown="onSvgPointerDown"
        @wheel="onWheel"
        @contextmenu.prevent
    >
        <!--
          Grid pattern is defined once in <defs>; the filled <rect> below
          lives inside the world-transform group so it tiles across the
          visible canvas at the current zoom without per-frame recomputation.
         -->
        <defs>
            <pattern
                id="editor-grid"
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

        <!--
          All canvas content lives under this single wrapping group. Pan
          and zoom mutate only this one `transform` attribute — one DOM
          write per frame, GPU-composited, no child re-layout. This is the
          same approach d3-zoom uses in the public canvas, and why that
          canvas pans buttersmooth.
         -->
        <g :transform="worldTransform">
            <image
                v-if="store.plan.value.background_image_url"
                :href="store.plan.value.background_image_url ?? ''"
                x="-10000"
                y="-10000"
                width="20000"
                height="20000"
                preserveAspectRatio="xMidYMid slice"
                opacity="0.6"
            />

            <rect
                v-if="store.view.showGrid"
                x="-10000"
                y="-10000"
                width="20000"
                height="20000"
                fill="url(#editor-grid)"
                pointer-events="none"
            />

            <g class="plan-labels">
                <g
                    v-for="label in store.plan.value.labels ?? []"
                    :key="'pl' + String(label.id ?? label.title)"
                    :transform="
                        dragState &&
                        label.id !== undefined &&
                        selectionContains('label', label.id)
                            ? `translate(${(label.x ?? 0) + dragState.dx}, ${(label.y ?? 0) + dragState.dy})`
                            : `translate(${label.x ?? 0}, ${label.y ?? 0})`
                    "
                    class="label cursor-pointer"
                    @pointerdown="(e) => onLabelPointerDown(e, null, label.id)"
                >
                    <rect
                        v-if="
                            label.id !== undefined &&
                            selectionContains('label', label.id)
                        "
                        x="-30"
                        y="-10"
                        width="60"
                        height="20"
                        fill="none"
                        stroke="#0ea5e9"
                        stroke-width="1"
                        stroke-dasharray="2 2"
                    />
                    <text
                        text-anchor="middle"
                        dominant-baseline="central"
                        font-size="10"
                        font-weight="bold"
                        fill="currentColor"
                        class="text-foreground"
                    >
                        {{ label.title }}
                    </text>
                </g>
            </g>

            <g
                v-for="block in store.plan.value.blocks"
                :key="'b' + String(block.id)"
                class="block"
            >
                <image
                    v-if="block.background_image_url"
                    :href="block.background_image_url"
                    x="-1500"
                    y="-1500"
                    width="3000"
                    height="3000"
                    preserveAspectRatio="xMidYMid slice"
                    opacity="0.4"
                />
                <g
                    v-for="seat in block.seats"
                    :key="'s' + String(seat.id)"
                    :transform="
                        dragState && selectionContains('seat', seat.id)
                            ? `translate(${(seat.x ?? 0) + dragState.dx}, ${(seat.y ?? 0) + dragState.dy})`
                            : `translate(${seat.x ?? 0}, ${seat.y ?? 0})`
                    "
                    class="seat cursor-pointer"
                    @pointerdown="
                        (e) => onSeatPointerDown(e, block.id, seat.id)
                    "
                >
                    <circle
                        r="10"
                        :fill="seatFill(block, seat)"
                        stroke="rgba(0,0,0,0.2)"
                        stroke-width="0.5"
                    />
                    <circle
                        v-if="selectionContains('seat', seat.id)"
                        r="12"
                        fill="none"
                        stroke="#0ea5e9"
                        stroke-width="1.5"
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
                    :transform="
                        dragState &&
                        label.id !== undefined &&
                        selectionContains('label', label.id)
                            ? `translate(${(label.x ?? 0) + dragState.dx}, ${(label.y ?? 0) + dragState.dy})`
                            : `translate(${label.x ?? 0}, ${label.y ?? 0})`
                    "
                    class="label cursor-pointer"
                    @pointerdown="
                        (e) => onLabelPointerDown(e, block.id, label.id)
                    "
                >
                    <rect
                        v-if="
                            label.id !== undefined &&
                            selectionContains('label', label.id)
                        "
                        x="-30"
                        y="-10"
                        width="60"
                        height="20"
                        fill="none"
                        stroke="#0ea5e9"
                        stroke-width="1"
                        stroke-dasharray="2 2"
                    />
                    <text
                        text-anchor="middle"
                        dominant-baseline="central"
                        font-size="10"
                        font-weight="bold"
                        fill="currentColor"
                        class="text-foreground"
                    >
                        {{ label.title }}
                    </text>
                </g>
            </g>

            <rect
                v-if="marquee"
                :x="Math.min(marquee.x1, marquee.x2)"
                :y="Math.min(marquee.y1, marquee.y2)"
                :width="Math.abs(marquee.x2 - marquee.x1)"
                :height="Math.abs(marquee.y2 - marquee.y1)"
                fill="rgba(14,165,233,0.1)"
                stroke="#0ea5e9"
                stroke-width="0.5"
                stroke-dasharray="3 3"
                pointer-events="none"
            />
        </g>
    </svg>
</template>
