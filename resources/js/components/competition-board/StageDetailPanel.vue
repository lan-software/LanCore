<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SlackBadge from './SlackBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { StageScheduleDto } from './types';

const props = defineProps<{
    open: boolean;
    stage: StageScheduleDto | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (
        e: 'save',
        payload: {
            stageId: string;
            estimated_duration_minutes?: number;
            reserve_buffer_minutes?: number;
            starts_at?: string | null;
            notes?: string | null;
            reset_to_computed?: boolean;
        },
    ): void;
}>();

const { t } = useI18n();

const duration = ref<number>(0);
const reserve = ref<number>(0);
const startsAt = ref<string>('');
const notes = ref<string>('');

watch(
    () => props.stage,
    (s) => {
        if (s) {
            duration.value = s.estimated_duration_minutes;
            reserve.value = s.reserve_buffer_minutes;
            startsAt.value = s.starts_at ? s.starts_at.slice(0, 16) : '';
            notes.value = s.notes ?? '';
        }
    },
    { immediate: true },
);

function save() {
    if (!props.stage) return;
    emit('save', {
        stageId: props.stage.id,
        estimated_duration_minutes: duration.value,
        reserve_buffer_minutes: reserve.value,
        starts_at: startsAt.value ? new Date(startsAt.value).toISOString() : null,
        notes: notes.value || null,
    });
}

function resetComputed() {
    if (!props.stage) return;
    emit('save', { stageId: props.stage.id, reset_to_computed: true });
}
</script>

<template>
    <div v-if="open && stage" class="fixed inset-y-0 right-0 z-40 w-80 border-l border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-800 dark:bg-zinc-950">
        <div class="mb-3 flex items-center justify-between">
            <div class="font-semibold">{{ stage.stage_name }}</div>
            <button class="text-zinc-400 hover:text-zinc-200" @click="emit('close')">×</button>
        </div>
        <div class="space-y-3">
            <label class="block text-xs">
                {{ t('competition_board.stage.starts_at') }}
                <Input v-model="startsAt" type="datetime-local" class="mt-1" />
            </label>
            <label class="block text-xs">
                {{ t('competition_board.stage.duration_minutes') }}
                <Input v-model.number="duration" type="number" min="5" class="mt-1" />
                <span v-if="stage.duration_overridden" class="mt-1 inline-block text-[10px] text-amber-600">
                    {{ t('competition_board.stage.overridden') }}
                </span>
            </label>
            <label class="block text-xs">
                {{ t('competition_board.stage.reserve_minutes') }}
                <Input v-model.number="reserve" type="number" min="0" class="mt-1" />
            </label>
            <div v-if="stage.slack_minutes !== null" class="flex justify-between text-xs">
                <span class="text-zinc-500">{{ t('competition_board.stage.slack') }}</span>
                <SlackBadge :minutes="stage.slack_minutes" />
            </div>
            <label class="block text-xs">
                {{ t('competition_board.stage.notes') }}
                <textarea
                    v-model="notes"
                    class="mt-1 w-full rounded border border-zinc-300 bg-transparent p-2 text-sm dark:border-zinc-700"
                    rows="3"
                />
            </label>
            <div class="flex justify-between gap-2 pt-2">
                <Button variant="secondary" size="sm" @click="resetComputed">
                    {{ t('competition_board.stage.reset_to_computed') }}
                </Button>
                <Button size="sm" @click="save">{{ t('common.save') }}</Button>
            </div>
        </div>
    </div>
</template>
