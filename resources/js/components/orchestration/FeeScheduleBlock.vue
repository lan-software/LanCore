<script setup lang="ts">
import { computed } from 'vue';
import { currencyFromCode, formatCents } from '@/lib/money';

const props = defineProps<{
    schedule: {
        percentage: number;
        fixed_cents: number;
        currency: string;
        note: string | null;
    };
}>();

const currency = computed(() => currencyFromCode(props.schedule.currency));
const fixedFormatted = computed(() =>
    formatCents(props.schedule.fixed_cents, currency.value),
);
const percentageFormatted = computed(() =>
    props.schedule.percentage.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
);
</script>

<template>
    <div
        class="mt-4 rounded-lg border border-sidebar-border/70 bg-card/40 p-3 text-xs dark:border-sidebar-border"
    >
        <p class="font-semibold tracking-wide text-muted-foreground uppercase">
            Provider fee
        </p>
        <p class="mt-1 font-mono text-sm">
            {{ percentageFormatted }}% + {{ fixedFormatted }} per transaction
        </p>
        <p v-if="schedule.note" class="mt-1 text-[11px] text-muted-foreground">
            {{ schedule.note }}
        </p>
    </div>
</template>
