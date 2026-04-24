<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2, Upload } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    seatPlanId: number;
    blockId?: number;
    currentUrl: string | null;
}>();

const emit = defineEmits<{
    uploaded: [url: string];
    removed: [];
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const uploading = ref(false);

function trigger(): void {
    inputRef.value?.click();
}

function onPick(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    uploading.value = true;
    const form = new FormData();
    form.append('image', file);

    const url = props.blockId
        ? `/seat-plans/${props.seatPlanId}/blocks/${props.blockId}/background`
        : `/seat-plans/${props.seatPlanId}/background`;

    router.post(url, form, {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: (page) => {
            const flash = (page.props?.flash ?? {}) as Record<string, unknown>;
            const uploadedUrl = flash.backgroundUrl as string | undefined;

            if (uploadedUrl) {
                emit('uploaded', uploadedUrl);
            } else {
                window.location.reload();
            }
        },
        onFinish: () => {
            uploading.value = false;

            if (inputRef.value) {
                inputRef.value.value = '';
            }
        },
    });
}

function remove(): void {
    const url = props.blockId
        ? `/seat-plans/${props.seatPlanId}/blocks/${props.blockId}/background`
        : `/seat-plans/${props.seatPlanId}/background`;

    router.delete(url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => emit('removed'),
    });
}
</script>

<template>
    <div class="space-y-2">
        <img
            v-if="currentUrl"
            :src="currentUrl"
            alt=""
            class="max-h-24 w-full rounded border object-cover"
        />
        <div class="flex gap-2">
            <input
                ref="inputRef"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                class="hidden"
                @change="onPick"
            />
            <Button
                type="button"
                size="sm"
                variant="outline"
                :disabled="uploading"
                @click="trigger"
            >
                <Upload class="size-4" />
                {{ uploading ? $t('common.saving') : $t('common.upload') }}
            </Button>
            <Button
                v-if="currentUrl"
                type="button"
                size="sm"
                variant="ghost"
                :disabled="uploading"
                @click="remove"
            >
                <Trash2 class="size-4" />
            </Button>
        </div>
    </div>
</template>
