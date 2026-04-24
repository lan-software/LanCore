<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';

defineProps<{
    title: string;
    value: string | number;
    subtext?: string;
    progress?: number;
}>();
</script>

<template>
    <Card>
        <CardContent class="flex flex-col gap-2">
            <div class="text-sm font-medium text-muted-foreground">
                {{ title }}
            </div>
            <div class="text-3xl font-semibold tabular-nums">
                {{ value }}
            </div>
            <div v-if="subtext" class="text-xs text-muted-foreground">
                {{ subtext }}
            </div>
            <div
                v-if="progress !== undefined"
                class="mt-1 h-2 w-full overflow-hidden rounded-full bg-muted"
            >
                <div
                    class="h-full bg-primary transition-all"
                    :style="{
                        width: `${Math.min(100, Math.max(0, progress))}%`,
                    }"
                />
            </div>
        </CardContent>
    </Card>
</template>
