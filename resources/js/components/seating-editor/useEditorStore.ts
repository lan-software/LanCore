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

    function findBlock(id: number | string): EditorBlock | null {
        const idStr = String(id);

        return plan.value.blocks.find((b) => String(b.id) === idStr) ?? null;
    }

    function findRow(
        blockId: number | string,
        rowId: number | string,
    ): EditorRow | null {
        const block = findBlock(blockId);

        if (!block) {
            return null;
        }

        const idStr = String(rowId);

        return block.rows?.find((r) => String(r.id) === idStr) ?? null;
    }

    function findSeat(
        blockId: number | string,
        seatId: number | string,
    ): EditorSeat | null {
        const block = findBlock(blockId);

        if (!block) {
            return null;
        }

        const idStr = String(seatId);

        return block.seats.find((s) => String(s.id) === idStr) ?? null;
    }

    function findLabel(
        blockId: number | string | undefined | null,
        labelId: number | string,
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
