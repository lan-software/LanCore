<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue';
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

const buttonDisabled = computed(() => countdown.value > 0);
const buttonLabel = computed(() => {
    if (countdown.value > 0) {
        return `Send ${props.priorAcceptorCount} emails and publish (${countdown.value}s)`;
    }

    return `Send ${props.priorAcceptorCount} emails and publish`;
});

onUnmounted(cleanup);
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent>
            <template v-if="step === 1">
                <DialogHeader>
                    <DialogTitle
                        >Non-editorial change — review impact</DialogTitle
                    >
                    <DialogDescription>
                        Publishing this version as a non-editorial change has
                        platform-wide consequences.
                    </DialogDescription>
                </DialogHeader>
                <ul class="list-disc space-y-2 pl-5 text-sm">
                    <li>
                        Email
                        <strong>{{ priorAcceptorCount }}</strong>
                        users who previously accepted this policy, with the new
                        policy attached as a PDF.
                    </li>
                    <li>
                        Require every active user to re-accept the policy on
                        their next login.
                    </li>
                </ul>
                <p class="mt-4 text-sm text-muted-foreground">
                    Editorial fixes (typos, formatting, link updates) should
                    leave the "non-editorial change" checkbox unchecked.
                </p>
                <DialogFooter>
                    <Button variant="outline" @click="cancel">Cancel</Button>
                    <Button @click="goToStep2">Continue</Button>
                </DialogFooter>
            </template>

            <template v-else>
                <DialogHeader>
                    <DialogTitle>Final confirmation</DialogTitle>
                    <DialogDescription>
                        <strong>{{ priorAcceptorCount }}</strong>
                        emails will be queued immediately. This cannot be
                        undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancel">Cancel</Button>
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
