<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SeatPlanController from '@/actions/App/Domain/Seating/Http/Controllers/SeatPlanController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { celebrateSave } from '@/components/seating-editor/celebrate';
import type {
    EditorPlan,
    IdMap,
} from '@/components/seating-editor/editor-types';
import EditorShell from '@/components/seating-editor/EditorShell.vue';
import type { SaveStatus } from '@/components/seating-editor/EditorToolbar.vue';
import SeatMapCanvas from '@/components/SeatMapCanvas.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as seatPlansRoute } from '@/routes/seat-plans';
import type { BreadcrumbItem } from '@/types';
import type { SeatPlan, SeatPlanData, TicketCategory } from '@/types/domain';
import BlockCategoryEditor from './partials/BlockCategoryEditor.vue';
import InvalidationConfirmDialog from './partials/InvalidationConfirmDialog.vue';
import type { InvalidationRow } from './partials/InvalidationConfirmDialog.vue';

const props = defineProps<{
    seatPlan: SeatPlan;
    events: { id: number; name: string }[];
    ticketCategories: Pick<TicketCategory, 'id' | 'name'>[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: seatPlansRoute().url },
    { title: 'Seat Plans', href: seatPlansRoute().url },
    {
        title: props.seatPlan.name,
        href: SeatPlanController.edit(props.seatPlan.id).url,
    },
];

const showDeleteDialog = ref(false);

function executeDelete() {
    router.delete(SeatPlanController.destroy(props.seatPlan.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}

const activeTab = ref<'editor' | 'preview' | 'categories' | 'json'>('editor');
const previewCanvasRef = ref<InstanceType<typeof SeatMapCanvas> | null>(null);

/*
 * The preview tab uses v-show, so the canvas is mounted while display:none.
 * The library's `calculateZoomLevels` divides by container width/height —
 * both 0 while hidden — which yields a zero-scale VENUE zoom and parks the
 * viewport at (0, 0). When the tab becomes visible, re-run the reset path
 * so it re-measures against the now-real container and fits the plan.
 */
watch(
    () => activeTab.value,
    (tab) => {
        if (tab !== 'preview') {
            return;
        }

        /* Two rAF ticks: one for Vue to apply `v-show`, one for the browser
         * to lay out the container so clientWidth / clientHeight are real. */
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                previewCanvasRef.value?.resetView?.();
            });
        });
    },
);

const initialPlan = computed<EditorPlan>(() => ({
    id: props.seatPlan.id,
    name: props.seatPlan.name,
    event_id: props.seatPlan.event_id,
    background_image_url: props.seatPlan.background_image_url ?? null,
    labels: (props.seatPlan.labels ?? []).map((label) => ({ ...label })),
    blocks: (props.seatPlan.blocks ?? []).map((block) => ({
        ...block,
        rows: block.rows ?? [],
    })),
}));

const workingPlan = ref<EditorPlan>(
    JSON.parse(JSON.stringify(initialPlan.value)),
);

function serialiseForServer(plan: EditorPlan): string {
    return JSON.stringify({ blocks: plan.blocks, labels: plan.labels ?? [] });
}

/**
 * Inertia's `useForm` exposes a public `.data()` method on the form instance.
 * A field literally named `data` would shadow it — `form.data = "..."` then
 * overwrites the method and `form.patch()` throws "typedForm.data is not a
 * function" when Inertia reads the payload. Keep the field as `dataJson` and
 * map it back to the server's `data` key via `.transform()`.
 */
const form = useForm<{
    name: string;
    dataJson: string;
    background_image_url: string | null;
    confirm_invalidations: boolean;
}>({
    name: initialPlan.value.name,
    dataJson: serialiseForServer(initialPlan.value),
    background_image_url: initialPlan.value.background_image_url ?? null,
    confirm_invalidations: false,
}).transform((d) => ({
    name: d.name,
    data: d.dataJson,
    background_image_url: d.background_image_url,
    confirm_invalidations: d.confirm_invalidations,
}));

const idMap = ref<IdMap | undefined>(undefined);
const saveStatus = ref<SaveStatus>('idle');
const errorMessage = ref<string | null>(null);
let savedPillTimeout: number | null = null;

function scheduleSavedPillReset(): void {
    if (savedPillTimeout !== null) {
        window.clearTimeout(savedPillTimeout);
    }

    savedPillTimeout = window.setTimeout(() => {
        if (saveStatus.value === 'saved') {
            saveStatus.value = 'idle';
        }

        savedPillTimeout = null;
    }, 2000);
}

function dismissError(): void {
    saveStatus.value = 'idle';
    errorMessage.value = null;
}

/**
 * Shape the editor's working copy the same way `SeatPlanResource` does on
 * the server for the public Picker/Welcome canvases:
 *
 *   1. Drop blocks with no seats — an empty block has degenerate bbox, which
 *      breaks the library's venue-fit zoom.
 *   2. Flatten plan-level labels into the first non-empty block — the
 *      @alisaitteke/seatmap-canvas library requires labels to live under a
 *      block (BlockModel.labels: LabelModel[]).
 *   3. Bake `block.seat_title_prefix` into each seat's title so the library
 *      renders "VIP-A1" rather than "A1".
 *
 * Mirrors `app/Domain/Seating/Http/Resources/SeatPlanResource.php`.
 */
const previewData = computed<SeatPlanData>(() => {
    const visibleBlocks = workingPlan.value.blocks.filter(
        (b) => (b.seats?.length ?? 0) > 0,
    );
    const planLabels = workingPlan.value.labels ?? [];

    return {
        background_image_url: workingPlan.value.background_image_url ?? null,
        blocks: visibleBlocks.map((block, index) => ({
            ...block,
            seats: block.seats.map((seat) => ({
                ...seat,
                title: (block.seat_title_prefix ?? '') + seat.title,
            })),
            labels:
                index === 0 ? [...block.labels, ...planLabels] : block.labels,
        })),
    };
});

const page = usePage<{
    flash: { invalidations?: InvalidationRow[]; id_map?: IdMap };
}>();
const flaggedInvalidations = ref<InvalidationRow[]>([]);
const showInvalidationDialog = ref(false);

watch(
    () => page.props.flash?.invalidations,
    (rows) => {
        if (rows && rows.length > 0) {
            flaggedInvalidations.value = rows;
            showInvalidationDialog.value = true;
        }
    },
    { immediate: true, deep: true },
);

watch(
    () => page.props.flash?.id_map,
    (map) => {
        if (map) {
            idMap.value = map;
        }
    },
    { immediate: true, deep: true },
);

function flattenErrors(
    errors: Record<string, string | string[] | undefined>,
): string {
    const parts: string[] = [];

    for (const value of Object.values(errors)) {
        if (Array.isArray(value)) {
            for (const v of value) {
                if (typeof v === 'string' && v.length > 0) {
                    parts.push(v);
                }
            }
        } else if (typeof value === 'string' && value.length > 0) {
            parts.push(value);
        }
    }

    return parts.join(' · ');
}

function save(nextPlan?: EditorPlan): void {
    if (nextPlan) {
        workingPlan.value = nextPlan;
    }

    form.name = workingPlan.value.name;
    form.dataJson = serialiseForServer(workingPlan.value);
    form.background_image_url = workingPlan.value.background_image_url ?? null;

    saveStatus.value = 'saving';
    errorMessage.value = null;

    form.patch(SeatPlanController.update(props.seatPlan.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            /* A successful save may still be a two-phase-invalidation pause —
             * detect it by the presence of the invalidations flash; only
             * celebrate when the plan actually persisted. */
            if ((page.props.flash?.invalidations ?? []).length > 0) {
                saveStatus.value = 'idle';

                return;
            }

            saveStatus.value = 'saved';
            celebrateSave(workingPlan.value);
            scheduleSavedPillReset();
        },
        onError: (errors) => {
            saveStatus.value = 'error';
            errorMessage.value =
                flattenErrors(
                    errors as Record<string, string | string[] | undefined>,
                ) || null;
        },
    });
}

function confirmInvalidations(): void {
    form.confirm_invalidations = true;
    saveStatus.value = 'saving';
    errorMessage.value = null;

    form.patch(SeatPlanController.update(props.seatPlan.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            saveStatus.value = 'saved';
            celebrateSave(workingPlan.value);
            scheduleSavedPillReset();
        },
        onError: (errors) => {
            saveStatus.value = 'error';
            errorMessage.value =
                flattenErrors(
                    errors as Record<string, string | string[] | undefined>,
                ) || null;
        },
        onFinish: () => {
            form.confirm_invalidations = false;
            showInvalidationDialog.value = false;
            flaggedInvalidations.value = [];
        },
    });
}

function cancelInvalidationDialog(): void {
    showInvalidationDialog.value = false;
    flaggedInvalidations.value = [];
    form.confirm_invalidations = false;
}

const categoryEditorData = computed<SeatPlanData>(() => ({
    blocks: workingPlan.value.blocks,
}));

function onCategoryEditorUpdate(next: SeatPlanData): void {
    workingPlan.value = {
        ...workingPlan.value,
        blocks: next.blocks.map((block) => ({
            ...block,
            rows:
                block.rows ??
                workingPlan.value.blocks.find(
                    (b) => String(b.id) === String(block.id),
                )?.rows ??
                [],
        })),
    };
}

/* Preview tab consumes `previewData` through `SeatMapCanvas.vue` the same
 * way the Welcome/Picker canvases do. Let the library auto-fit on init —
 * with empty blocks filtered out of `previewData` the venue bbox is clean
 * and zoom works identically to the public-facing pages. */

const jsonValue = computed<string>({
    get() {
        return JSON.stringify(workingPlan.value, null, 2);
    },
    set(v) {
        try {
            const parsed = JSON.parse(v) as EditorPlan;
            workingPlan.value = parsed;
        } catch {
            // ignore invalid JSON mid-typing
        }
    },
});
</script>

<template>
    <Head :title="`Edit ${seatPlan.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Link
                    :href="seatPlansRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Seat Plans
                </Link>

                <div class="flex items-center gap-2">
                    <div class="grid gap-1">
                        <Label for="name" class="sr-only">Name</Label>
                        <Input
                            id="name"
                            v-model="workingPlan.name"
                            placeholder="Plan name"
                            class="w-64"
                        />
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>

            <div class="flex gap-1 border-b">
                <button
                    type="button"
                    class="border-b-2 px-3 py-2 text-sm transition-colors"
                    :class="
                        activeTab === 'editor'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'editor'"
                >
                    {{ $t('seating.admin.editor.tabs.editor') }}
                </button>
                <button
                    type="button"
                    class="border-b-2 px-3 py-2 text-sm transition-colors"
                    :class="
                        activeTab === 'preview'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'preview'"
                >
                    {{ $t('seating.admin.editor.tabs.preview') }}
                </button>
                <button
                    type="button"
                    class="border-b-2 px-3 py-2 text-sm transition-colors"
                    :class="
                        activeTab === 'categories'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'categories'"
                >
                    {{ $t('seating.admin.editor.tabs.categories') }}
                </button>
                <button
                    type="button"
                    class="border-b-2 px-3 py-2 text-sm transition-colors"
                    :class="
                        activeTab === 'json'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'json'"
                >
                    {{ $t('seating.admin.editor.tabs.advancedJson') }}
                </button>
            </div>

            <div v-show="activeTab === 'editor'" class="flex-1">
                <EditorShell
                    :initial="initialPlan"
                    :processing="form.processing"
                    :id-map="idMap"
                    :save-status="saveStatus"
                    :error-message="errorMessage"
                    @save="save"
                    @dismiss-error="dismissError"
                />
            </div>

            <div
                v-show="activeTab === 'preview'"
                class="flex-1 overflow-hidden rounded-md border"
            >
                <!-- Matches the public Welcome canvas verbatim (same style
                     overrides and same SeatMapCanvas wrapper). The
                     server-side transformations that Welcome benefits from
                     are replicated on `previewData` above. A ref lets us
                     trigger a re-fit when the tab becomes visible (the
                     container has `display:none` while hidden, so the
                     library's initial measurement is all zeros). -->
                <SeatMapCanvas
                    ref="previewCanvasRef"
                    :data="previewData"
                    :options="{
                        legend: true,
                        style: {
                            seat: {
                                hover: '#8fe100',
                                color: '#6796ff',
                                not_salable: '#424747',
                            },
                        },
                    }"
                />
            </div>

            <div v-show="activeTab === 'categories'" class="flex-1 space-y-3">
                <Heading
                    variant="small"
                    :title="$t('seating.admin.categoryEditorTitle')"
                    :description="$t('seating.admin.categoryEditorDescription')"
                />
                <BlockCategoryEditor
                    :data="categoryEditorData"
                    :ticket-categories="ticketCategories"
                    @update:data="onCategoryEditorUpdate"
                />
                <Button @click="save()" :disabled="form.processing">
                    {{
                        form.processing
                            ? $t('common.saving')
                            : $t('seating.admin.editor.toolbar.save')
                    }}
                </Button>
            </div>

            <div v-show="activeTab === 'json'" class="flex-1 space-y-3">
                <Label for="dataJson">Seat Plan JSON</Label>
                <Textarea
                    id="dataJson"
                    v-model="jsonValue"
                    rows="24"
                    class="font-mono text-sm"
                />
                <InputError :message="form.errors.data" />
                <Button @click="save()" :disabled="form.processing">
                    {{
                        form.processing
                            ? $t('common.saving')
                            : $t('seating.admin.editor.toolbar.save')
                    }}
                </Button>
            </div>
        </div>

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ seatPlan.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The seat plan and all its
                        data will be permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="executeDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <InvalidationConfirmDialog
            v-model:open="showInvalidationDialog"
            :invalidations="flaggedInvalidations"
            :processing="form.processing"
            @confirm="confirmInvalidations"
            @cancel="cancelInvalidationDialog"
        />
    </AppLayout>
</template>
