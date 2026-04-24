<script setup lang="ts">
import { ref, watch } from 'vue';
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
import type { EditorBlock } from './editor-types';
import { newClientId } from './geometry';

const props = defineProps<{
    open: boolean;
    existingTitles?: string[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    create: [block: EditorBlock];
}>();

const title = ref('Block');
const color = ref('#2c3e50');
const seatTitlePrefix = ref('');
const mode = ref<'empty' | 'grid'>('grid');
const rows = ref(5);
const cols = ref(10);
const pitch = ref(30);
const originX = ref(0);
const originY = ref(0);

/**
 * Pick the first `Block-A1 … Block-Z4` slot that isn't already in use.
 * Wraps back to `Block-A1` if every slot is taken (unlikely but harmless).
 */
function nextAvailableBlockName(taken: Set<string>): string {
    for (let letter = 0; letter < 26; letter++) {
        for (let number = 1; number <= 4; number++) {
            const candidate = `Block-${String.fromCharCode(65 + letter)}${number}`;

            if (!taken.has(candidate)) {
                return candidate;
            }
        }
    }

    return 'Block-A1';
}

watch(
    () => props.open,
    (o) => {
        if (o) {
            const taken = new Set(
                (props.existingTitles ?? []).map((t) => t.trim()),
            );
            title.value = nextAvailableBlockName(taken);
            color.value = '#2c3e50';
            seatTitlePrefix.value = '';
            mode.value = 'grid';
            rows.value = 5;
            cols.value = 10;
            pitch.value = 30;
            originX.value = 0;
            originY.value = 0;
        }
    },
);

function submit(): void {
    const prefix = seatTitlePrefix.value.trim();
    const block: EditorBlock = {
        id: newClientId('new-block'),
        title: title.value,
        color: color.value,
        seat_title_prefix: prefix === '' ? null : prefix,
        background_image_url: null,
        sort_order: 0,
        allowed_ticket_category_ids: [],
        rows: [],
        seats: [],
        labels: [],
    };

    if (mode.value === 'grid') {
        const rowBase = 'A'.charCodeAt(0);

        for (let r = 0; r < rows.value; r++) {
            const rowName = String.fromCharCode(rowBase + r);
            const rowId = newClientId('new-row');
            block.rows.push({
                id: rowId,
                name: rowName,
                sort_order: r,
            });

            block.labels.push({
                id: newClientId('new-label'),
                title: `Row ${rowName}`,
                x: originX.value - pitch.value,
                y: originY.value + r * pitch.value,
                sort_order: r,
            });

            for (let c = 0; c < cols.value; c++) {
                block.seats.push({
                    id: newClientId('new-seat'),
                    row_id: rowId,
                    number: c + 1,
                    title: `${rowName}${c + 1}`,
                    x: originX.value + c * pitch.value,
                    y: originY.value + r * pitch.value,
                    salable: true,
                });
            }
        }
    }

    emit('create', block);
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => $emit('update:open', v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{
                    $t('seating.admin.editor.addBlockDialog.title')
                }}</DialogTitle>
                <DialogDescription>{{
                    $t('seating.admin.editor.addBlockDialog.description')
                }}</DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label>{{
                        $t('seating.admin.editor.properties.block.name')
                    }}</Label>
                    <Input v-model="title" />
                </div>

                <div class="grid gap-2">
                    <Label>{{
                        $t('seating.admin.editor.properties.block.color')
                    }}</Label>
                    <Input v-model="color" type="color" />
                </div>

                <div class="grid gap-2">
                    <Label>{{
                        $t(
                            'seating.admin.editor.addBlockDialog.seatTitlePrefix',
                        )
                    }}</Label>
                    <Input v-model="seatTitlePrefix" placeholder="VIP-" />
                    <p class="text-xs text-muted-foreground">
                        {{
                            $t(
                                'seating.admin.editor.properties.block.seatTitlePrefixHint',
                            )
                        }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label>{{
                        $t('seating.admin.editor.addBlockDialog.mode')
                    }}</Label>
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            size="sm"
                            :variant="mode === 'empty' ? 'default' : 'outline'"
                            @click="mode = 'empty'"
                        >
                            {{
                                $t(
                                    'seating.admin.editor.addBlockDialog.modeEmpty',
                                )
                            }}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="mode === 'grid' ? 'default' : 'outline'"
                            @click="mode = 'grid'"
                        >
                            {{
                                $t(
                                    'seating.admin.editor.addBlockDialog.modeGrid',
                                )
                            }}
                        </Button>
                    </div>
                </div>

                <div v-if="mode === 'grid'" class="grid grid-cols-2 gap-2">
                    <div class="grid gap-1">
                        <Label>{{
                            $t('seating.admin.editor.addBlockDialog.gridRows')
                        }}</Label>
                        <Input
                            type="number"
                            min="1"
                            max="40"
                            v-model.number="rows"
                        />
                    </div>
                    <div class="grid gap-1">
                        <Label>{{
                            $t('seating.admin.editor.addBlockDialog.gridCols')
                        }}</Label>
                        <Input
                            type="number"
                            min="1"
                            max="80"
                            v-model.number="cols"
                        />
                    </div>
                    <div class="grid gap-1">
                        <Label>{{
                            $t('seating.admin.editor.addBlockDialog.gridPitch')
                        }}</Label>
                        <Input
                            type="number"
                            min="10"
                            max="200"
                            v-model.number="pitch"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-1">
                        <div class="grid gap-1">
                            <Label>X</Label>
                            <Input type="number" v-model.number="originX" />
                        </div>
                        <div class="grid gap-1">
                            <Label>Y</Label>
                            <Input type="number" v-model.number="originY" />
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="$emit('update:open', false)"
                >
                    {{ $t('common.cancel') }}
                </Button>
                <Button type="button" @click="submit">
                    {{ $t('common.create') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
