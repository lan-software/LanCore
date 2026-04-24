<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import BackgroundImageUpload from './BackgroundImageUpload.vue';
import type { EntityRef } from './editor-types';
import type { EditorStore } from './useEditorStore';

const props = defineProps<{
    store: EditorStore;
    seatPlanId: number;
}>();

defineEmits<{
    mutate: [];
}>();

const selection = computed(() => props.store.selection.value);

const single = computed(() => {
    if (selection.value.length !== 1) {
        return null;
    }

    return selection.value[0];
});

const multiSelectionCount = computed(() => selection.value.length);

/**
 * Block IDs present in the selection (used to exclude same-block destinations
 * from the Move-to-block dropdown).
 */
const selectedBlockIds = computed(() => {
    const ids = new Set<string>();

    for (const ref of selection.value) {
        if (ref.kind === 'seat' && ref.blockId !== undefined) {
            ids.add(String(ref.blockId));
        }
    }

    return ids;
});

const moveTargetOptions = computed(() => {
    return props.store.plan.value.blocks
        .filter((b) => !selectedBlockIds.value.has(String(b.id)))
        .map((b) => ({ id: String(b.id), label: b.title || `#${b.id}` }));
});

function updatePlanName(name: string): void {
    props.store.applyMutation('rename-plan', (draft) => {
        draft.name = name;
    });
}

function updatePlanBackground(url: string | null): void {
    props.store.applyMutation('plan-background', (draft) => {
        draft.background_image_url = url;
    });
}

function updateBlockField<
    K extends 'title' | 'color' | 'seat_title_prefix' | 'background_image_url',
>(blockId: number | string, field: K, value: string | null): void {
    props.store.applyMutation(`block-${field}`, (draft) => {
        const block = draft.blocks.find(
            (b) => String(b.id) === String(blockId),
        );

        if (block) {
            const normalised =
                field === 'seat_title_prefix' && value === '' ? null : value;
            (block as Record<string, unknown>)[field] = normalised;
        }
    });
}

function updateSeatField(
    blockId: number | string,
    seatId: number | string,
    field: 'title' | 'note' | 'color' | 'salable',
    value: unknown,
): void {
    props.store.applyMutation(`seat-${field}`, (draft) => {
        const block = draft.blocks.find(
            (b) => String(b.id) === String(blockId),
        );

        if (!block) {
            return;
        }

        const seat = block.seats.find((s) => String(s.id) === String(seatId));

        if (!seat) {
            return;
        }

        (seat as Record<string, unknown>)[field] = value;
    });
}

function updateLabelField(
    blockId: number | string | null | undefined,
    labelId: number | string,
    field: 'title' | 'x' | 'y',
    value: string | number,
): void {
    props.store.applyMutation(`label-${field}`, (draft) => {
        const collection =
            blockId === null || blockId === undefined || blockId === ''
                ? (draft.labels ?? [])
                : (draft.blocks.find((b) => String(b.id) === String(blockId))
                      ?.labels ?? []);

        const label = collection.find(
            (l) => l.id !== undefined && String(l.id) === String(labelId),
        );

        if (!label) {
return;
}

        if (field === 'title') {
            label.title = String(value);
        } else if (field === 'x') {
            label.x = Number(value) || 0;
        } else if (field === 'y') {
            label.y = Number(value) || 0;
        }
    });
}

function moveLabelToBlock(targetId: string): void {
    const labelRefs = selection.value.filter((r) => r.kind === 'label');

    if (labelRefs.length === 0) {
return;
}

    const labelIds = new Set(labelRefs.map((r) => String(r.id)));

    props.store.applyMutation('move-label-to-block', (draft) => {
        const target = draft.blocks.find((b) => String(b.id) === targetId);

        if (!target) {
return;
}

        for (const block of draft.blocks) {
            if (String(block.id) === targetId) {
continue;
}

            const keep: typeof block.labels = [];

            for (const label of block.labels) {
                if (label.id !== undefined && labelIds.has(String(label.id))) {
                    target.labels.push(label);
                } else {
                    keep.push(label);
                }
            }

            block.labels = keep;
        }
    });

    const refreshed: EntityRef[] = [];

    for (const block of props.store.plan.value.blocks) {
        for (const label of block.labels) {
            if (label.id !== undefined && labelIds.has(String(label.id))) {
                refreshed.push({
                    kind: 'label',
                    id: label.id,
                    blockId: block.id,
                });
            }
        }
    }

    props.store.setSelection(refreshed);
}

function massUpdateSalable(value: boolean): void {
    const selectedIds = new Set(
        selection.value
            .filter((r) => r.kind === 'seat')
            .map((r) => String(r.id)),
    );
    props.store.applyMutation('mass-salable', (draft) => {
        for (const block of draft.blocks) {
            for (const seat of block.seats) {
                if (selectedIds.has(String(seat.id))) {
                    seat.salable = value;
                }
            }
        }
    });
}

function moveSelectionToBlock(targetId: string): void {
    const seatIds = new Set(
        selection.value
            .filter((r) => r.kind === 'seat')
            .map((r) => String(r.id)),
    );

    if (seatIds.size === 0) {
return;
}

    props.store.applyMutation('move-to-block', (draft) => {
        const target = draft.blocks.find((b) => String(b.id) === targetId);

        if (!target) {
return;
}

        for (const block of draft.blocks) {
            if (String(block.id) === targetId) {
continue;
}

            const keep: typeof block.seats = [];

            for (const seat of block.seats) {
                if (seatIds.has(String(seat.id))) {
                    seat.row_id = null;
                    target.seats.push(seat);
                } else {
                    keep.push(seat);
                }
            }

            block.seats = keep;
        }
    });

    /* Keep the selection pointing at the same seats, but in their new block. */
    const newSelection: EntityRef[] = [];

    for (const block of props.store.plan.value.blocks) {
        for (const seat of block.seats) {
            if (seatIds.has(String(seat.id))) {
                newSelection.push({
                    kind: 'seat',
                    id: seat.id,
                    blockId: block.id,
                });
            }
        }
    }

    props.store.setSelection(newSelection);
}

function deleteSelected(): void {
    const seatIds = new Set(
        selection.value
            .filter((r) => r.kind === 'seat')
            .map((r) => String(r.id)),
    );
    const labelIds = new Set(
        selection.value
            .filter((r) => r.kind === 'label')
            .map((r) => String(r.id)),
    );
    const blockIds = new Set(
        selection.value
            .filter((r) => r.kind === 'block')
            .map((r) => String(r.id)),
    );

    props.store.applyMutation('delete-selection', (draft) => {
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
    props.store.clearSelection();
}

const singleBlock = computed(() => {
    if (!single.value || single.value.kind !== 'block') {
        return null;
    }

    return props.store.findBlock(single.value.id);
});

const singleSeat = computed(() => {
    if (!single.value || single.value.kind !== 'seat') {
        return null;
    }

    return props.store.findSeat(single.value.blockId ?? '', single.value.id);
});

const singleLabel = computed(() => {
    if (!single.value || single.value.kind !== 'label') {
        return null;
    }

    return props.store.findLabel(single.value.blockId ?? '', single.value.id);
});

/**
 * Blocks available as a move-target for the selected label. Omitted for
 * plan-level labels (blockId undefined) — moving block ↔ plan is a v2 task.
 */
const labelMoveTargetOptions = computed(() => {
    if (!singleLabel.value) {
return [];
}

    const currentBlockId = single.value?.blockId;

    if (currentBlockId === undefined || currentBlockId === null) {
        return [];
    }

    return props.store.plan.value.blocks
        .filter((b) => String(b.id) !== String(currentBlockId))
        .map((b) => ({ id: String(b.id), label: b.title || `#${b.id}` }));
});

const singleLabelIsPlanLevel = computed(() => {
    if (!singleLabel.value) {
return false;
}

    return (
        single.value?.blockId === undefined || single.value?.blockId === null
    );
});
</script>

<template>
    <aside class="flex flex-col gap-4 border-l bg-card p-3 text-sm">
        <section v-if="multiSelectionCount > 1" class="space-y-3">
            <h3 class="font-semibold">
                {{ $t('seating.admin.editor.mass.title') }} ({{
                    multiSelectionCount
                }})
            </h3>
            <div class="flex gap-2">
                <Button
                    size="sm"
                    variant="outline"
                    @click="massUpdateSalable(true)"
                >
                    {{ $t('seating.admin.editor.mass.setSalableTrue') }}
                </Button>
                <Button
                    size="sm"
                    variant="outline"
                    @click="massUpdateSalable(false)"
                >
                    {{ $t('seating.admin.editor.mass.setSalableFalse') }}
                </Button>
            </div>
            <div v-if="moveTargetOptions.length > 0" class="grid gap-2">
                <Label>{{ $t('seating.admin.editor.mass.moveToBlock') }}</Label>
                <Select
                    @update:model-value="(v) => moveSelectionToBlock(String(v))"
                >
                    <SelectTrigger>
                        <SelectValue
                            :placeholder="
                                $t('seating.admin.editor.mass.moveToBlock')
                            "
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="opt in moveTargetOptions"
                            :key="opt.id"
                            :value="opt.id"
                        >
                            {{ opt.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <Button
                size="sm"
                variant="destructive"
                class="w-full"
                @click="deleteSelected"
            >
                <Trash2 class="size-4" />
                {{ $t('seating.admin.editor.mass.delete') }}
            </Button>
        </section>

        <section v-else-if="singleSeat" class="space-y-3">
            <h3 class="font-semibold">
                {{ $t('seating.admin.editor.properties.seat.title') }}
            </h3>
            <div class="grid gap-2">
                <Label>{{
                    $t('seating.admin.editor.properties.seat.titleField')
                }}</Label>
                <Input
                    :model-value="singleSeat.title"
                    @update:model-value="
                        (v: string | number) =>
                            updateSeatField(
                                single!.blockId!,
                                single!.id,
                                'title',
                                String(v),
                            )
                    "
                />
            </div>
            <div class="flex items-center gap-2">
                <Switch
                    :model-value="singleSeat.salable"
                    @update:model-value="
                        (v: boolean) =>
                            updateSeatField(
                                single!.blockId!,
                                single!.id,
                                'salable',
                                v,
                            )
                    "
                />
                <span>{{
                    $t('seating.admin.editor.properties.seat.salable')
                }}</span>
            </div>
            <div class="grid gap-2">
                <Label>{{
                    $t('seating.admin.editor.properties.seat.color')
                }}</Label>
                <Input
                    type="color"
                    :model-value="singleSeat.color ?? '#6796ff'"
                    @update:model-value="
                        (v: string | number) =>
                            updateSeatField(
                                single!.blockId!,
                                single!.id,
                                'color',
                                String(v),
                            )
                    "
                />
            </div>
            <div class="grid gap-2">
                <Label>{{
                    $t('seating.admin.editor.properties.seat.note')
                }}</Label>
                <Textarea
                    :model-value="singleSeat.note ?? ''"
                    rows="3"
                    @update:model-value="
                        (v: string | number) =>
                            updateSeatField(
                                single!.blockId!,
                                single!.id,
                                'note',
                                String(v),
                            )
                    "
                />
            </div>
            <div v-if="moveTargetOptions.length > 0" class="grid gap-2">
                <Label>{{ $t('seating.admin.editor.mass.moveToBlock') }}</Label>
                <Select
                    @update:model-value="(v) => moveSelectionToBlock(String(v))"
                >
                    <SelectTrigger>
                        <SelectValue
                            :placeholder="
                                $t('seating.admin.editor.mass.moveToBlock')
                            "
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="opt in moveTargetOptions"
                            :key="opt.id"
                            :value="opt.id"
                        >
                            {{ opt.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <Button
                size="sm"
                variant="destructive"
                class="w-full"
                @click="deleteSelected"
            >
                <Trash2 class="size-4" />
                {{ $t('common.delete') }}
            </Button>
        </section>

        <section v-else-if="singleLabel" class="space-y-3">
            <h3 class="font-semibold">
                {{ $t('seating.admin.editor.properties.label.title') }}
                <span
                    v-if="singleLabelIsPlanLevel"
                    class="ml-1 text-xs font-normal text-muted-foreground"
                >
                    ({{
                        $t('seating.admin.editor.properties.label.planLevel')
                    }})
                </span>
            </h3>
            <div class="grid gap-2">
                <Label>{{
                    $t('seating.admin.editor.properties.label.text')
                }}</Label>
                <Input
                    :model-value="singleLabel.title"
                    @update:model-value="
                        (v: string | number) =>
                            updateLabelField(
                                single?.blockId ?? null,
                                single!.id,
                                'title',
                                String(v),
                            )
                    "
                />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="grid gap-1">
                    <Label>X</Label>
                    <Input
                        type="number"
                        :model-value="singleLabel.x"
                        @update:model-value="
                            (v: string | number) =>
                                updateLabelField(
                                    single?.blockId ?? null,
                                    single!.id,
                                    'x',
                                    Number(v),
                                )
                        "
                    />
                </div>
                <div class="grid gap-1">
                    <Label>Y</Label>
                    <Input
                        type="number"
                        :model-value="singleLabel.y"
                        @update:model-value="
                            (v: string | number) =>
                                updateLabelField(
                                    single?.blockId ?? null,
                                    single!.id,
                                    'y',
                                    Number(v),
                                )
                        "
                    />
                </div>
            </div>
            <div
                v-if="
                    !singleLabelIsPlanLevel && labelMoveTargetOptions.length > 0
                "
                class="grid gap-2"
            >
                <Label>{{ $t('seating.admin.editor.mass.moveToBlock') }}</Label>
                <Select
                    @update:model-value="(v) => moveLabelToBlock(String(v))"
                >
                    <SelectTrigger>
                        <SelectValue
                            :placeholder="
                                $t('seating.admin.editor.mass.moveToBlock')
                            "
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="opt in labelMoveTargetOptions"
                            :key="opt.id"
                            :value="opt.id"
                        >
                            {{ opt.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <Button
                size="sm"
                variant="destructive"
                class="w-full"
                @click="deleteSelected"
            >
                <Trash2 class="size-4" />
                {{ $t('common.delete') }}
            </Button>
        </section>

        <section v-else-if="singleBlock" class="space-y-3">
            <h3 class="font-semibold">
                {{ $t('seating.admin.editor.properties.block.title') }}
            </h3>
            <div class="grid gap-2">
                <Label>{{
                    $t('seating.admin.editor.properties.block.name')
                }}</Label>
                <Input
                    :model-value="singleBlock.title"
                    @update:model-value="
                        (v: string | number) =>
                            updateBlockField(
                                singleBlock!.id,
                                'title',
                                String(v),
                            )
                    "
                />
            </div>
            <div class="grid gap-2">
                <Label>{{
                    $t('seating.admin.editor.properties.block.color')
                }}</Label>
                <Input
                    type="color"
                    :model-value="singleBlock.color"
                    @update:model-value="
                        (v: string | number) =>
                            updateBlockField(
                                singleBlock!.id,
                                'color',
                                String(v),
                            )
                    "
                />
            </div>
            <div class="grid gap-2">
                <Label>{{
                    $t('seating.admin.editor.properties.block.seatTitlePrefix')
                }}</Label>
                <Input
                    :model-value="singleBlock.seat_title_prefix ?? ''"
                    :placeholder="'VIP-'"
                    @update:model-value="
                        (v: string | number) =>
                            updateBlockField(
                                singleBlock!.id,
                                'seat_title_prefix',
                                String(v),
                            )
                    "
                />
                <p class="text-xs text-muted-foreground">
                    {{
                        $t(
                            'seating.admin.editor.properties.block.seatTitlePrefixHint',
                        )
                    }}
                </p>
            </div>
            <div v-if="typeof singleBlock.id === 'number'" class="grid gap-2">
                <Label>{{
                    $t(
                        'seating.admin.editor.properties.block.backgroundImageUrl',
                    )
                }}</Label>
                <BackgroundImageUpload
                    :seat-plan-id="seatPlanId"
                    :block-id="singleBlock.id"
                    :current-url="singleBlock.background_image_url ?? null"
                    @uploaded="
                        (url) =>
                            updateBlockField(
                                singleBlock!.id,
                                'background_image_url',
                                url,
                            )
                    "
                    @removed="
                        () =>
                            updateBlockField(
                                singleBlock!.id,
                                'background_image_url',
                                null,
                            )
                    "
                />
            </div>
            <Button
                size="sm"
                variant="destructive"
                class="w-full"
                @click="deleteSelected"
            >
                <Trash2 class="size-4" />
                {{ $t('common.delete') }}
            </Button>
        </section>

        <section v-else class="space-y-3">
            <h3 class="font-semibold">
                {{ $t('seating.admin.editor.properties.plan.title') }}
            </h3>
            <div class="grid gap-2">
                <Label>{{
                    $t('seating.admin.editor.properties.plan.name')
                }}</Label>
                <Input
                    :model-value="store.plan.value.name"
                    @update:model-value="
                        (v: string | number) => updatePlanName(String(v))
                    "
                />
            </div>
            <div class="grid gap-2">
                <Label>{{
                    $t(
                        'seating.admin.editor.properties.plan.backgroundImageUrl',
                    )
                }}</Label>
                <BackgroundImageUpload
                    :seat-plan-id="seatPlanId"
                    :current-url="store.plan.value.background_image_url ?? null"
                    @uploaded="(url) => updatePlanBackground(url)"
                    @removed="() => updatePlanBackground(null)"
                />
            </div>
            <p class="text-xs text-muted-foreground">
                {{ $t('seating.admin.editor.emptyCanvasHint') }}
            </p>
        </section>
    </aside>
</template>
