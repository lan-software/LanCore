<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
    priorAcceptorCount: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirmed: [];
}>();

const step = ref<1 | 2>(1);
const countdown = ref(0);
let timer: number | null = null;

watch(
    () => props.open,
    (open) => {
        cleanup();

        if (open) {
            step.value = 1;
            countdown.value = 0;
        }
    },
);

function startCountdown(): void {
    countdown.value = 5;
    timer = window.setInterval(() => {
        countdown.value -= 1;

        if (countdown.value <= 0) {
            cleanup();
        }
    }, 1000);
}

function cleanup(): void {
    if (timer !== null) {
        window.clearInterval(timer);
        timer = null;
    }
}

function goToStep2(): void {
    step.value = 2;
    startCountdown();
}

function cancel(): void {
    cleanup();
    emit('update:open', false);
}

function confirm(): void {
    cleanup();
    emit('update:open', false);
    emit('confirmed');
}

const { t } = useI18n();

const buttonDisabled = computed(() => countdown.value > 0);
const buttonLabel = computed(() => {
    if (countdown.value > 0) {
        return t(
            'policies.admin.version_create.confirm.step2_button_disabled',
            { seconds: countdown.value },
        );
    }

    return t('policies.admin.version_create.confirm.step2_button', {
        count: props.priorAcceptorCount,
    });
});

onUnmounted(cleanup);
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent>
            <template v-if="step === 1">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            $t(
                                'policies.admin.version_create.confirm.step1_title',
                            )
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            $t(
                                'policies.admin.version_create.confirm.step1_body',
                                { count: priorAcceptorCount },
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancel">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button @click="goToStep2">
                        {{
                            $t(
                                'policies.admin.version_create.confirm.step1_continue',
                            )
                        }}
                    </Button>
                </DialogFooter>
            </template>

            <template v-else>
                <DialogHeader>
                    <DialogTitle>
                        {{
                            $t(
                                'policies.admin.version_create.confirm.step2_title',
                            )
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            $t(
                                'policies.admin.version_create.confirm.step2_body',
                                { count: priorAcceptorCount },
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancel">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button
                        :disabled="buttonDisabled"
                        variant="destructive"
                        @click="confirm"
                    >
                        {{ buttonLabel }}
                    </Button>
                </DialogFooter>
            </template>
        </DialogContent>
    </Dialog>
</template>
