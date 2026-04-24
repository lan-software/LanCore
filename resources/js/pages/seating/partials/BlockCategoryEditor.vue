<script setup lang="ts">
import { Armchair } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import type {
    SeatPlanBlock,
    SeatPlanData,
    TicketCategory,
} from '@/types/domain';

type BlockWithCategories = SeatPlanBlock & {
    allowed_ticket_category_ids?: number[] | null;
};

const props = defineProps<{
    data: SeatPlanData;
    ticketCategories: Pick<TicketCategory, 'id' | 'name'>[];
}>();

const emit = defineEmits<{
    'update:data': [data: SeatPlanData];
}>();

const blocks = computed<BlockWithCategories[]>(
    () => (props.data.blocks ?? []) as BlockWithCategories[],
);

function isSelected(blockId: string | number, categoryId: number): boolean {
    const block = blocks.value.find((b) => String(b.id) === String(blockId));

    return (block?.allowed_ticket_category_ids ?? []).includes(categoryId);
}

function toggle(
    blockId: string | number,
    categoryId: number,
    nextState: boolean | 'indeterminate',
): void {
    const nextBlocks = blocks.value.map((block) => {
        if (String(block.id) !== String(blockId)) {
            return block;
        }

        const current = block.allowed_ticket_category_ids ?? [];
        const next = nextState
            ? Array.from(new Set([...current, categoryId]))
            : current.filter((id) => id !== categoryId);

        return { ...block, allowed_ticket_category_ids: next };
    });

    emit('update:data', { ...props.data, blocks: nextBlocks } as SeatPlanData);
}

function clearAll(blockId: string | number): void {
    const nextBlocks = blocks.value.map((block) => {
        if (String(block.id) !== String(blockId)) {
            return block;
        }

        return { ...block, allowed_ticket_category_ids: [] };
    });
    emit('update:data', { ...props.data, blocks: nextBlocks } as SeatPlanData);
}
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="blocks.length === 0"
            class="rounded-xl border border-dashed bg-card p-4 text-sm text-muted-foreground"
        >
            {{ $t('seating.admin.noBlocks') }}
        </div>

        <div
            v-for="block in blocks"
            :key="String(block.id)"
            class="rounded-xl border bg-card p-3 shadow-sm"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-block h-3 w-3 rounded-sm"
                            :style="{
                                backgroundColor: block.color ?? '#e2e2e2',
                            }"
                            aria-hidden="true"
                        />
                        <span class="truncate font-medium">{{
                            block.title ?? block.id
                        }}</span>
                        <Badge
                            variant="outline"
                            class="gap-1 text-xs text-muted-foreground"
                        >
                            <Armchair class="size-3" />
                            {{ block.seats?.length ?? 0 }}
                        </Badge>
                    </div>
                </div>
                <button
                    type="button"
                    class="text-xs text-muted-foreground hover:text-foreground"
                    @click="clearAll(block.id)"
                >
                    {{ $t('seating.admin.clearCategories') }}
                </button>
            </div>

            <div
                v-if="ticketCategories.length === 0"
                class="mt-3 text-sm text-muted-foreground"
            >
                {{ $t('seating.admin.noCategoriesForEvent') }}
            </div>
            <div
                v-else
                class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-3"
            >
                <label
                    v-for="cat in ticketCategories"
                    :key="cat.id"
                    :for="`block-${block.id}-cat-${cat.id}`"
                    class="flex cursor-pointer items-center gap-2 text-sm"
                >
                    <Checkbox
                        :id="`block-${block.id}-cat-${cat.id}`"
                        :model-value="isSelected(block.id, cat.id)"
                        @update:model-value="(v) => toggle(block.id, cat.id, v)"
                    />
                    <span class="truncate">{{ cat.name }}</span>
                </label>
            </div>

            <p class="mt-2 text-xs text-muted-foreground">
                <template
                    v-if="
                        !block.allowed_ticket_category_ids ||
                        block.allowed_ticket_category_ids.length === 0
                    "
                >
                    {{ $t('seating.admin.blockOpenToAll') }}
                </template>
                <template v-else>
                    {{
                        $t('seating.admin.blockRestrictedTo', {
                            count: block.allowed_ticket_category_ids.length,
                        })
                    }}
                </template>
            </p>
        </div>
    </div>
</template>
