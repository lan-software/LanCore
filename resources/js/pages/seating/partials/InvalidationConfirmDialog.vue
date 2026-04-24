<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export interface InvalidationRow {
    assignment_id: number;
    ticket_id: number;
    user_id: number;
    seat_id: string;
    seat_title: string | null;
    block_id: string | null;
    block_title: string | null;
    assignee_name: string | null;
    reason: 'seat_removed' | 'category_mismatch';
}

defineProps<{
    open: boolean;
    invalidations: InvalidationRow[];
    processing?: boolean;
}>();

const emit = defineEmits<{
    confirm: [];
    cancel: [];
    'update:open': [value: boolean];
}>();

function onOpenUpdate(value: boolean): void {
    emit('update:open', value);

    if (!value) {
        emit('cancel');
    }
}

function seatLabel(row: InvalidationRow): string {
    return row.seat_title ?? row.seat_id;
}
</script>

<template>
    <Dialog :open="open" @update:open="onOpenUpdate">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <AlertTriangle
                        class="size-5 text-amber-600 dark:text-amber-400"
                    />
                    {{ $t('seating.admin.invalidationDialog.title') }}
                </DialogTitle>
                <DialogDescription>
                    {{ $t('seating.admin.invalidationDialog.description') }}
                </DialogDescription>
            </DialogHeader>

            <ul
                class="max-h-72 space-y-2 overflow-y-auto rounded-md border p-2"
            >
                <li
                    v-for="row in invalidations"
                    :key="row.assignment_id"
                    class="flex items-center justify-between gap-2 text-sm"
                >
                    <span class="min-w-0 truncate">
                        <span class="font-medium">{{
                            row.assignee_name ?? '—'
                        }}</span>
                        <span class="mx-1 text-muted-foreground">·</span>
                        <span class="text-muted-foreground">{{
                            $t('seating.admin.invalidationDialog.ticket', {
                                id: row.ticket_id,
                            })
                        }}</span>
                        <span class="mx-1 text-muted-foreground">·</span>
                        <span class="font-mono">{{ seatLabel(row) }}</span>
                    </span>
                    <Badge
                        :variant="
                            row.reason === 'seat_removed'
                                ? 'destructive'
                                : 'secondary'
                        "
                        class="shrink-0 text-xs"
                    >
                        {{
                            row.reason === 'seat_removed'
                                ? $t(
                                      'seating.admin.invalidationDialog.reason.seat_removed',
                                  )
                                : $t(
                                      'seating.admin.invalidationDialog.reason.category_mismatch',
                                  )
                        }}
                    </Badge>
                </li>
            </ul>

            <p class="text-xs text-muted-foreground">
                {{ $t('seating.admin.invalidationDialog.notificationHint') }}
            </p>

            <DialogFooter>
                <Button variant="outline" @click="emit('cancel')">
                    {{ $t('seating.admin.invalidationDialog.cancel') }}
                </Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{
                        processing
                            ? $t('common.saving')
                            : $t('seating.admin.invalidationDialog.confirm')
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
