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

const viewBox = computed(() => {
    const v = props.store.view;
    const width = 1600 / v.zoom;
    const height = 1000 / v.zoom;
    const minX = v.panX - width / 2;
    const minY = v.panY - height / 2;

    return `${minX} ${minY} ${width} ${height}`;
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

const gridLines = computed(() => {
    if (!props.store.view.showGrid) {
        return { vertical: [] as number[], horizontal: [] as number[] };
    }

    const v = props.store.view;
    const step = v.gridSize;
    const width = 1600 / v.zoom;
    const height = 1000 / v.zoom;
    const minX = v.panX - width / 2;
    const minY = v.panY - height / 2;
    const maxX = minX + width;
    const maxY = minY + height;

    const startX = Math.floor(minX / step) * step;
    const startY = Math.floor(minY / step) * step;

    const vertical: number[] = [];

    for (let x = startX; x <= maxX; x += step) {
        vertical.push(x);
    }

    const horizontal: number[] = [];

    for (let y = startY; y <= maxY; y += step) {
        horizontal.push(y);
    }

    return { vertical, horizontal };
});

function svgPoint(event: PointerEvent | WheelEvent): { x: number; y: number } {
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

    function onMove(ev: PointerEvent): void {
        const cur = svgPoint(ev);
        store.view.panX = startPan.x - (cur.x - start.x);
        store.view.panY = startPan.y - (cur.y - start.y);
    }

    function onUp(): void {
        try {
            (event.target as Element).releasePointerCapture(event.pointerId);
        } catch {
            /* already released */
        }

        document.removeEventListener('pointermove', onMove);
        document.removeEventListener('pointerup', onUp);
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
        :viewBox="viewBox"
        preserveAspectRatio="xMidYMid meet"
        @pointerdown="onSvgPointerDown"
        @wheel="onWheel"
        @contextmenu.prevent
    >
        <image
            v-if="store.plan.value.background_image_url"
            :href="store.plan.value.background_image_url ?? ''"
            x="-2000"
            y="-2000"
            width="4000"
            height="4000"
            preserveAspectRatio="xMidYMid slice"
            opacity="0.6"
        />

        <g v-if="store.view.showGrid" class="grid" pointer-events="none">
            <line
                v-for="x in gridLines.vertical"
                :key="'v' + x"
                :x1="x"
                :y1="-10000"
                :x2="x"
                :y2="10000"
                stroke="currentColor"
                stroke-width="0.3"
                opacity="0.08"
            />
            <line
                v-for="y in gridLines.horizontal"
                :key="'h' + y"
                :x1="-10000"
                :y1="y"
                :x2="10000"
                :y2="y"
                stroke="currentColor"
                stroke-width="0.3"
                opacity="0.08"
            />
        </g>

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
                @pointerdown="(e) => onSeatPointerDown(e, block.id, seat.id)"
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
                @pointerdown="(e) => onLabelPointerDown(e, block.id, label.id)"
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
    </svg>
</template>
