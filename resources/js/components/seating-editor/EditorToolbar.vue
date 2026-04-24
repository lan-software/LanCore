<script setup lang="ts">
import {
    Check,
    Hand,
    Loader2,
    MapPinPlus,
    MousePointer2,
    Plus,
    Redo2,
    Save,
    Square,
    Tag,
    Trash2,
    Undo2,
    X,
    ZoomIn,
    ZoomOut,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import type { ToolMode } from './editor-types';
import type { EditorStore } from './useEditorStore';

export type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';

const props = defineProps<{
    store: EditorStore;
    processing?: boolean;
    saveStatus?: SaveStatus;
    errorMessage?: string | null;
}>();

/* Local alias so the eslint `vue/no-mutating-props` rule doesn't trip on
 * mutations of nested reactive fields the store is designed to expose. */
const store = props.store;

defineEmits<{
    save: [];
    'add-block': [];
    'dismiss-error': [];
}>();

function pickTool(mode: ToolMode): void {
    store.tool.value = mode;
}

function zoomIn(): void {
    store.view.zoom = Math.min(store.view.zoom * 1.25, 5);
}

function zoomOut(): void {
    store.view.zoom = Math.max(store.view.zoom * 0.8, 0.1);
}

/**
 * Center + zoom-to-fit on the plan's bounding box. Falls back to origin
 * when the plan has no content. Prevents the admin from getting stranded
 * after dragging content far from origin or after the offset-on-create
 * lands new blocks off-screen.
 */
function resetView(): void {
    const plan = store.plan.value;
    let minX = Number.POSITIVE_INFINITY;
    let maxX = Number.NEGATIVE_INFINITY;
    let minY = Number.POSITIVE_INFINITY;
    let maxY = Number.NEGATIVE_INFINITY;

    const accumulate = (x: number, y: number) => {
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
    };

    for (const block of plan.blocks) {
        for (const seat of block.seats) {
            accumulate(seat.x, seat.y);
        }

        for (const label of block.labels) {
            accumulate(label.x, label.y);
        }
    }

    for (const label of plan.labels ?? []) {
        accumulate(label.x, label.y);
    }

    if (!Number.isFinite(minX)) {
        store.view.zoom = 1;
        store.view.panX = 0;
        store.view.panY = 0;

        return;
    }

    const centerX = (minX + maxX) / 2;
    const centerY = (minY + maxY) / 2;
    const width = Math.max(maxX - minX, 200);
    const height = Math.max(maxY - minY, 200);
    /* The SVG viewBox is 1600×1000 at zoom 1; find the zoom that fits the
     * bbox with ~10% margin on each side. Clamp to the existing 0.1–5 range. */
    const margin = 1.2;
    const zoomFit = Math.min(1600 / (width * margin), 1000 / (height * margin));
    store.view.zoom = Math.min(Math.max(zoomFit, 0.1), 5);
    store.view.panX = centerX;
    store.view.panY = centerY;
}
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-2 rounded-md border bg-card p-2"
    >
        <div class="flex items-center gap-1">
            <Button
                type="button"
                size="sm"
                :variant="store.tool.value === 'select' ? 'default' : 'outline'"
                @click="pickTool('select')"
                :title="$t('seating.admin.editor.toolbar.tool.select')"
            >
                <MousePointer2 class="size-4" />
            </Button>
            <Button
                type="button"
                size="sm"
                :variant="
                    store.tool.value === 'add-seat' ? 'default' : 'outline'
                "
                @click="pickTool('add-seat')"
                :title="$t('seating.admin.editor.toolbar.tool.addSeat')"
            >
                <Plus class="size-4" />
            </Button>
            <Button
                type="button"
                size="sm"
                :variant="
                    store.tool.value === 'add-label' ? 'default' : 'outline'
                "
                @click="pickTool('add-label')"
                :title="$t('seating.admin.editor.toolbar.tool.addLabel')"
            >
                <Tag class="size-4" />
            </Button>
            <Button
                type="button"
                size="sm"
                variant="outline"
                @click="$emit('add-block')"
                :title="$t('seating.admin.editor.toolbar.tool.addBlock')"
            >
                <Square class="size-4" />
                <MapPinPlus class="-ml-1 size-4" />
            </Button>
            <Button
                type="button"
                size="sm"
                :variant="
                    store.tool.value === 'delete' ? 'destructive' : 'outline'
                "
                @click="pickTool('delete')"
                :title="$t('seating.admin.editor.toolbar.tool.delete')"
            >
                <Trash2 class="size-4" />
            </Button>
            <Button
                type="button"
                size="sm"
                :variant="store.tool.value === 'pan' ? 'default' : 'outline'"
                @click="pickTool('pan')"
                :title="$t('seating.admin.editor.toolbar.tool.pan')"
            >
                <Hand class="size-4" />
            </Button>
        </div>

        <div class="mx-2 h-6 w-px bg-border" />

        <div class="flex items-center gap-2 text-xs">
            <Switch
                :model-value="store.view.snapEnabled"
                @update:model-value="
                    (v: boolean) => (store.view.snapEnabled = v)
                "
            />
            <span>{{ $t('seating.admin.editor.toolbar.snap') }}</span>
        </div>

        <div class="mx-2 h-6 w-px bg-border" />

        <div class="flex items-center gap-1">
            <Button type="button" size="sm" variant="outline" @click="zoomOut">
                <ZoomOut class="size-4" />
            </Button>
            <Button type="button" size="sm" variant="outline" @click="zoomIn">
                <ZoomIn class="size-4" />
            </Button>
            <Button
                type="button"
                size="sm"
                variant="ghost"
                :title="$t('seating.admin.editor.toolbar.zoomReset')"
                @click="resetView"
            >
                {{ Math.round(store.view.zoom * 100) }}%
            </Button>
        </div>

        <div class="mx-2 h-6 w-px bg-border" />

        <div class="flex items-center gap-1">
            <Button
                type="button"
                size="sm"
                variant="outline"
                :disabled="!store.canUndo.value"
                @click="store.undo"
                :title="$t('seating.admin.editor.toolbar.undo')"
            >
                <Undo2 class="size-4" />
            </Button>
            <Button
                type="button"
                size="sm"
                variant="outline"
                :disabled="!store.canRedo.value"
                @click="store.redo"
                :title="$t('seating.admin.editor.toolbar.redo')"
            >
                <Redo2 class="size-4" />
            </Button>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <span
                v-if="saveStatus === 'saving'"
                class="inline-flex items-center gap-1 rounded bg-muted px-2 py-1 text-xs text-muted-foreground"
            >
                <Loader2 class="size-3 animate-spin" />
                {{ $t('seating.admin.editor.toolbar.saving') }}
            </span>
            <span
                v-else-if="saveStatus === 'saved'"
                class="inline-flex items-center gap-1 rounded bg-emerald-500/10 px-2 py-1 text-xs text-emerald-700 dark:text-emerald-400"
            >
                <Check class="size-3" />
                {{ $t('seating.admin.editor.toolbar.saved') }}
            </span>
            <span
                v-else-if="saveStatus === 'error'"
                class="inline-flex max-w-xs items-center gap-1 truncate rounded bg-red-500/10 px-2 py-1 text-xs text-red-700 dark:text-red-400"
                :title="errorMessage ?? undefined"
            >
                {{
                    errorMessage ??
                    $t('seating.admin.editor.toolbar.saveFailedGeneric')
                }}
                <button
                    type="button"
                    class="ml-1 inline-flex items-center text-red-700 hover:opacity-80 dark:text-red-400"
                    :aria-label="$t('seating.admin.editor.toolbar.dismiss')"
                    @click="$emit('dismiss-error')"
                >
                    <X class="size-3" />
                </button>
            </span>
            <span
                v-else-if="store.isDirty.value"
                class="rounded bg-amber-500/10 px-2 py-1 text-xs text-amber-700 dark:text-amber-400"
            >
                {{ $t('seating.admin.editor.toolbar.unsavedChanges') }}
            </span>
            <Button
                type="button"
                size="sm"
                :disabled="processing"
                @click="$emit('save')"
            >
                <Save class="size-4" />
                {{
                    processing
                        ? $t('common.saving')
                        : $t('seating.admin.editor.toolbar.save')
                }}
            </Button>
        </div>
    </div>
</template>
