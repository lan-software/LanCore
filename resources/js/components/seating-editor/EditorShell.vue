<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import AddBlockDialog from './AddBlockDialog.vue';
import type { EditorBlock, EditorPlan, EntityRef, IdMap } from './editor-types';
import EditorCanvas from './EditorCanvas.vue';
import EditorToolbar from './EditorToolbar.vue';
import type {SaveStatus} from './EditorToolbar.vue';
import { newClientId, snapToGrid } from './geometry';
import PropertiesPanel from './PropertiesPanel.vue';
import { useEditorStore } from './useEditorStore';

const props = defineProps<{
    initial: EditorPlan;
    processing?: boolean;
    idMap?: IdMap;
    saveStatus?: SaveStatus;
    errorMessage?: string | null;
}>();

const emit = defineEmits<{
    save: [plan: EditorPlan];
    'dismiss-error': [];
}>();

const store = useEditorStore(props.initial);

watch(
    () => props.idMap,
    (map) => {
        if (map) {
            store.reconcileIds(map);
        }
    },
    { deep: true },
);

const showAddBlock = ref(false);

function handleCanvasClick(event: { x: number; y: number }): void {
    if (store.tool.value === 'add-seat') {
        addSeatAt(event.x, event.y);
    } else if (store.tool.value === 'add-label') {
        addLabelAt(event.x, event.y);
    }
}

function findOrCreateDefaultRow(block: EditorBlock): string | number {
    const defaultRow =
        block.rows.find((r) => r.name === '(default)') ?? block.rows[0];

    if (defaultRow) {
        return defaultRow.id;
    }

    const id = newClientId('new-row');
    block.rows.push({ id, name: '(default)', sort_order: block.rows.length });

    return id;
}

function addSeatAt(x: number, y: number): void {
    store.applyMutation('add-seat', (draft) => {
        let targetBlock = draft.blocks[0];

        if (!targetBlock) {
            targetBlock = {
                id: newClientId('new-block'),
                title: 'Main',
                color: '#2c3e50',
                seat_title_prefix: null,
                background_image_url: null,
                sort_order: 0,
                allowed_ticket_category_ids: [],
                rows: [],
                seats: [],
                labels: [],
            };
            draft.blocks.push(targetBlock);
        }

        const rowId = findOrCreateDefaultRow(targetBlock);
        const existingCount = targetBlock.seats.length;
        const snappedX = store.view.snapEnabled
            ? snapToGrid(x, store.view.gridSize)
            : Math.round(x);
        const snappedY = store.view.snapEnabled
            ? snapToGrid(y, store.view.gridSize)
            : Math.round(y);

        targetBlock.seats.push({
            id: newClientId('new-seat'),
            row_id: rowId,
            number: existingCount + 1,
            title: `S${existingCount + 1}`,
            x: snappedX,
            y: snappedY,
            salable: true,
        });
    });
}

function addLabelAt(x: number, y: number): void {
    store.applyMutation('add-label', (draft) => {
        const snappedX = store.view.snapEnabled
            ? snapToGrid(x, store.view.gridSize)
            : Math.round(x);
        const snappedY = store.view.snapEnabled
            ? snapToGrid(y, store.view.gridSize)
            : Math.round(y);

        if (!draft.labels) {
            draft.labels = [];
        }

        draft.labels.push({
            id: newClientId('new-label'),
            title: 'Label',
            x: snappedX,
            y: snappedY,
            sort_order: draft.labels.length,
        });
    });
}

function createBlock(block: EditorBlock): void {
    const offset = computeNextBlockOffset();

    if (offset !== 0) {
        for (const seat of block.seats) {
            seat.x += offset;
        }

        for (const label of block.labels) {
            label.x += offset;
        }
    }

    store.applyMutation('add-block', (draft) => {
        draft.blocks.push(block);
    });

    /* Auto-select the new block so the admin can drag/mass-edit it immediately. */
    if (block.seats.length > 0) {
        const refs: EntityRef[] = block.seats.map((seat) => ({
            kind: 'seat',
            id: seat.id,
            blockId: block.id,
        }));
        store.setSelection(refs);
    } else {
        store.setSelection([{ kind: 'block', id: block.id }]);
    }

    store.tool.value = 'select';

    /* Pan the editor viewport to the new block so the admin can see it
     * without hunting. The block offset can push content far to the right
     * of the default pan (0, 0); without this, adding a block makes the
     * canvas appear empty and the admin zooms out to find it. */
    focusOnBlock(block);
}

function focusOnBlock(block: EditorBlock): void {
    if (block.seats.length === 0 && block.labels.length === 0) {
        return;
    }

    let minX = Number.POSITIVE_INFINITY;
    let maxX = Number.NEGATIVE_INFINITY;
    let minY = Number.POSITIVE_INFINITY;
    let maxY = Number.NEGATIVE_INFINITY;

    for (const seat of block.seats) {
        if (seat.x < minX) {
minX = seat.x;
}

        if (seat.x > maxX) {
maxX = seat.x;
}

        if (seat.y < minY) {
minY = seat.y;
}

        if (seat.y > maxY) {
maxY = seat.y;
}
    }

    for (const label of block.labels) {
        if (label.x < minX) {
minX = label.x;
}

        if (label.x > maxX) {
maxX = label.x;
}

        if (label.y < minY) {
minY = label.y;
}

        if (label.y > maxY) {
maxY = label.y;
}
    }

    if (!Number.isFinite(minX)) {
return;
}

    store.view.panX = (minX + maxX) / 2;
    store.view.panY = (minY + maxY) / 2;
    store.view.zoom = 1;
}

/**
 * Distance to shift a newly-created block so it does not overlap existing
 * content. Looks at every existing seat (and block labels) and returns the
 * rightmost X + a gap. Returns 0 if the plan is empty.
 */
function computeNextBlockOffset(): number {
    const gap = 60;
    let maxX = Number.NEGATIVE_INFINITY;

    for (const block of store.plan.value.blocks) {
        for (const seat of block.seats) {
            if (seat.x > maxX) {
maxX = seat.x;
}
        }

        for (const label of block.labels) {
            if (label.x > maxX) {
maxX = label.x;
}
        }
    }

    for (const label of store.plan.value.labels ?? []) {
        if (label.x > maxX) {
maxX = label.x;
}
    }

    if (maxX === Number.NEGATIVE_INFINITY) {
return 0;
}

    return maxX + gap;
}

function save(): void {
    emit('save', JSON.parse(JSON.stringify(store.plan.value)) as EditorPlan);
}

function onKeydown(event: KeyboardEvent): void {
    const target = event.target as HTMLElement | null;

    if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) {
        return;
    }

    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'z') {
        event.preventDefault();

        if (event.shiftKey) {
            store.redo();
        } else {
            store.undo();
        }

        return;
    }

    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
        event.preventDefault();
        save();

        return;
    }

    if (event.key === 'Delete' || event.key === 'Backspace') {
        if (store.selection.value.length > 0) {
            event.preventDefault();
            deleteSelection();
        }

        return;
    }

    if (event.key === 'Escape') {
        store.clearSelection();
        store.tool.value = 'select';

        return;
    }

    if (['v', 'V'].includes(event.key)) {
store.tool.value = 'select';
}

    if (['s', 'S'].includes(event.key)) {
store.tool.value = 'add-seat';
}

    if (['l', 'L'].includes(event.key)) {
store.tool.value = 'add-label';
}

    if (['d', 'D'].includes(event.key)) {
        store.tool.value = 'delete';
        store.clearSelection();
    }

    if ([' '].includes(event.key)) {
store.tool.value = 'pan';
}
}

function deleteSelection(): void {
    const seatIds = new Set(
        store.selection.value
            .filter((r) => r.kind === 'seat')
            .map((r) => String(r.id)),
    );
    const labelIds = new Set(
        store.selection.value
            .filter((r) => r.kind === 'label')
            .map((r) => String(r.id)),
    );
    const blockIds = new Set(
        store.selection.value
            .filter((r) => r.kind === 'block')
            .map((r) => String(r.id)),
    );

    store.applyMutation('delete-selection', (draft) => {
        draft.blocks = draft.blocks.filter((b) => !blockIds.has(String(b.id)));

        for (const block of draft.blocks) {
            block.seats = block.seats.filter((s) => !seatIds.has(String(s.id)));
            block.labels = block.labels.filter(
                (l) => l.id === undefined || !labelIds.has(String(l.id)),
            );
        }

        draft.labels = (draft.labels ?? []).filter(
            (l) => l.id === undefined || !labelIds.has(String(l.id)),
        );
    });
    store.clearSelection();
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
});

defineExpose({ store });
</script>

<template>
    <div class="flex h-[calc(100vh-12rem)] flex-col gap-3">
        <EditorToolbar
            :store="store"
            :processing="processing"
            :save-status="saveStatus"
            :error-message="errorMessage"
            @save="save"
            @add-block="showAddBlock = true"
            @dismiss-error="$emit('dismiss-error')"
        />

        <div class="flex flex-1 gap-3 overflow-hidden">
            <div class="flex-1 overflow-hidden rounded-md border">
                <EditorCanvas
                    :store="store"
                    @canvas-click="handleCanvasClick"
                />
            </div>
            <div class="w-72 overflow-y-auto">
                <PropertiesPanel :store="store" :seat-plan-id="initial.id" />
            </div>
        </div>

        <AddBlockDialog
            v-model:open="showAddBlock"
            :existing-titles="store.plan.value.blocks.map((b) => b.title)"
            @create="createBlock"
        />
    </div>
</template>
