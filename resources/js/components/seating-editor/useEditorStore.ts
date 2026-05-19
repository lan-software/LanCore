import { computed, reactive, ref } from 'vue';
import type {
    EditorBlock,
    EditorLabel,
    EditorPlan,
    EditorRow,
    EditorSeat,
    EditorSnapshot,
    EntityRef,
    IdMap,
    ToolMode,
    ViewState,
} from './editor-types';

type Draft = EditorPlan;

type UseEditorStoreReturn = ReturnType<typeof useEditorStore>;

export type EditorStore = UseEditorStoreReturn;

/**
 * Working-copy state + 50-step snapshot undo/redo for the admin seat-plan
 * editor. Snapshots are full plan JSON clones — simple, predictable, handles
 * any mutation shape.
 */
export function useEditorStore(initial: EditorPlan) {
    const normalised: EditorPlan = {
        ...initial,
        labels: initial.labels ?? [],
        blocks: (initial.blocks ?? []).map((block) => ({
            ...block,
            rows: block.rows ?? [],
            seats: block.seats ?? [],
            labels: block.labels ?? [],
        })),
    };
    const plan = ref<EditorPlan>(clone(normalised));
    const past = ref<EditorSnapshot[]>([]);
    const future = ref<EditorSnapshot[]>([]);
    /* Baseline mirrors the normalised plan (same shape as `plan.value`) so
     * `isDirty` doesn't trip on purely-defaulted fields like `labels: []`
     * or `rows: []` that the normaliser filled in. */
    const savedBaseline = ref<string>(serialise(normalised));

    const tool = ref<ToolMode>('select');
    const selection = ref<EntityRef[]>([]);
    const view = reactive<ViewState>({
        zoom: 1,
        panX: 0,
        panY: 0,
        snapEnabled: true,
        gridSize: 15,
        showGrid: true,
    });

    const isDirty = computed(
        () => serialise(plan.value) !== savedBaseline.value,
    );
    const canUndo = computed(() => past.value.length > 0);
    const canRedo = computed(() => future.value.length > 0);

    /**
     * Lower bound for `view.zoom`, derived from the plan's content extent:
     * the zoom at which everything just fits the 1600×1000 viewport with a
     * 1.2 padding factor. Capped at 1× from above so a tiny/empty plan can
     * still be edited at 100 %, and floored at 0.05× so degenerate plans
     * don't yield zero. Reactive on the plan, so adding/removing blocks
     * relaxes or tightens the limit automatically.
     */
    const minZoom = computed<number>(() => {
        const ABSOLUTE_MIN = 0.05;
        let minX = Number.POSITIVE_INFINITY;
        let maxX = Number.NEGATIVE_INFINITY;
        let minY = Number.POSITIVE_INFINITY;
        let maxY = Number.NEGATIVE_INFINITY;

        function accumulate(x: number, y: number): void {
            if (x < minX) {
                minX = x;
            }

            if (x > maxX) {
                maxX = x;
            }

            if (y < minY) {
                minY = y;
            }

            if (y > maxY) {
                maxY = y;
            }
        }

        for (const block of plan.value.blocks) {
            for (const seat of block.seats) {
                accumulate(seat.x, seat.y);
            }

            for (const label of block.labels) {
                accumulate(label.x, label.y);
            }
        }

        for (const label of plan.value.labels ?? []) {
            accumulate(label.x, label.y);
        }

        if (!Number.isFinite(minX)) {
            return ABSOLUTE_MIN;
        }

        const width = Math.max(maxX - minX, 200);
        const height = Math.max(maxY - minY, 200);
        const margin = 1.2;
        const fitZoom = Math.min(
            1600 / (width * margin),
            1000 / (height * margin),
        );

        return Math.max(ABSOLUTE_MIN, Math.min(fitZoom, 1));
    });

    function clone<T>(value: T): T {
        return JSON.parse(JSON.stringify(value)) as T;
    }

    function serialise(value: EditorPlan): string {
        return JSON.stringify(value);
    }

    function applyMutation(label: string, fn: (draft: Draft) => void): void {
        past.value.push({ plan: clone(plan.value), label, at: Date.now() });

        if (past.value.length > 50) {
            past.value.shift();
        }

        future.value = [];

        const draft = clone(plan.value);
        fn(draft);
        plan.value = draft;
    }

    function undo(): void {
        const snapshot = past.value.pop();

        if (!snapshot) {
            return;
        }

        future.value.push({
            plan: clone(plan.value),
            label: 'redo',
            at: Date.now(),
        });
        plan.value = snapshot.plan;
    }

    function redo(): void {
        const snapshot = future.value.pop();

        if (!snapshot) {
            return;
        }

        past.value.push({
            plan: clone(plan.value),
            label: 'undo',
            at: Date.now(),
        });
        plan.value = snapshot.plan;
    }

    function markSaved(): void {
        savedBaseline.value = serialise(plan.value);
        past.value = [];
        future.value = [];
    }

    function reconcileIds(map: IdMap | undefined): void {
        if (!map) {
            return;
        }

        const draft = clone(plan.value);

        for (const block of draft.blocks) {
            const blockId = String(block.id);

            if (map.blocks[blockId] !== undefined) {
                block.id = map.blocks[blockId];
            }

            if (block.rows) {
                for (const row of block.rows) {
                    const rowId = String(row.id);

                    if (map.rows[rowId] !== undefined) {
                        row.id = map.rows[rowId];
                    }
                }
            }

            for (const seat of block.seats) {
                const seatId = String(seat.id);

                if (map.seats[seatId] !== undefined) {
                    seat.id = map.seats[seatId];
                }

                const rowRef =
                    seat.row_id !== undefined && seat.row_id !== null
                        ? String(seat.row_id)
                        : null;

                if (rowRef !== null && map.rows[rowRef] !== undefined) {
                    seat.row_id = map.rows[rowRef];
                }
            }

            for (const label of block.labels) {
                const labelId =
                    label.id === undefined ? null : String(label.id);

                if (labelId !== null && map.labels[labelId] !== undefined) {
                    label.id = map.labels[labelId];
                }
            }
        }

        for (const label of draft.labels ?? []) {
            const labelId = label.id === undefined ? null : String(label.id);

            if (labelId !== null && map.labels[labelId] !== undefined) {
                label.id = map.labels[labelId];
            }
        }

        plan.value = draft;
        markSaved();
    }

    function setSelection(refs: EntityRef[]): void {
        selection.value = refs;
    }

    function addToSelection(ref: EntityRef): void {
        const existing = selection.value.find(
            (r) => r.kind === ref.kind && String(r.id) === String(ref.id),
        );

        if (existing) {
            selection.value = selection.value.filter(
                (r) =>
                    !(r.kind === ref.kind && String(r.id) === String(ref.id)),
            );
        } else {
            selection.value = [...selection.value, ref];
        }
    }

    function clearSelection(): void {
        selection.value = [];
    }

    function findBlock(id: string | string): EditorBlock | null {
        const idStr = String(id);

        return plan.value.blocks.find((b) => String(b.id) === idStr) ?? null;
    }

    function findRow(
        blockId: string | string,
        rowId: string | string,
    ): EditorRow | null {
        const block = findBlock(blockId);

        if (!block) {
            return null;
        }

        const idStr = String(rowId);

        return block.rows?.find((r) => String(r.id) === idStr) ?? null;
    }

    function findSeat(
        blockId: string | string,
        seatId: string | string,
    ): EditorSeat | null {
        const block = findBlock(blockId);

        if (!block) {
            return null;
        }

        const idStr = String(seatId);

        return block.seats.find((s) => String(s.id) === idStr) ?? null;
    }

    function findLabel(
        blockId: string | string | undefined | null,
        labelId: string | string,
    ): EditorLabel | null {
        const idStr = String(labelId);

        if (blockId === undefined || blockId === null || blockId === '') {
            return (
                (plan.value.labels ?? []).find(
                    (l) => String(l.id ?? '') === idStr,
                ) ?? null
            );
        }

        const block = findBlock(blockId);

        if (!block) {
            return null;
        }

        return block.labels.find((l) => String(l.id ?? '') === idStr) ?? null;
    }

    return {
        plan,
        tool,
        selection,
        view,
        isDirty,
        canUndo,
        canRedo,
        minZoom,
        applyMutation,
        undo,
        redo,
        markSaved,
        reconcileIds,
        setSelection,
        addToSelection,
        clearSelection,
        findBlock,
        findRow,
        findSeat,
        findLabel,
    };
}
