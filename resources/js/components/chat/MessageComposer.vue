<script setup lang="ts">
// @see docs/mil-std-498/SRS.md CHT-F-018, CHT-F-026
import ChatMentionSearchController from '@/actions/App/Domain/Chat/Http/Controllers/ChatMentionSearchController';
import ChatMessageController from '@/actions/App/Domain/Chat/Http/Controllers/ChatMessageController';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { ChatUserRef } from './types';

const props = defineProps<{
    roomId: number;
    disabled: boolean;
    disabledReason?: string | null;
}>();

const { t } = useI18n();

const form = useForm({ body: '' });
const textareaEl = ref<HTMLTextAreaElement | null>(null);
const mentionOpen = ref(false);
const mentionQuery = ref('');
const mentionTokenStart = ref(-1);
const mentionResults = ref<ChatUserRef[]>([]);
const mentionLoading = ref(false);
const mentionAbort = ref<AbortController | null>(null);

const placeholder = computed(() =>
    props.disabled
        ? (props.disabledReason ?? '')
        : t('chat.composer.placeholder'),
);

watch(mentionQuery, async (q) => {
    if (!mentionOpen.value || q === '') {
        mentionResults.value = [];
        return;
    }
    mentionAbort.value?.abort();
    const ac = new AbortController();
    mentionAbort.value = ac;
    mentionLoading.value = true;
    try {
        const url =
            ChatMentionSearchController({ room: props.roomId }).url +
            `?q=${encodeURIComponent(q)}`;
        const res = await fetch(url, {
            signal: ac.signal,
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const data = (await res.json()) as { users: ChatUserRef[] };
            mentionResults.value = data.users;
        }
    } catch {
        // ignore — aborted or transient
    } finally {
        mentionLoading.value = false;
    }
});

onBeforeUnmount(() => {
    mentionAbort.value?.abort();
});

function onInput(event: Event): void {
    const target = event.target as HTMLTextAreaElement;
    form.body = target.value;
    detectMentionTrigger(target);
}

function detectMentionTrigger(target: HTMLTextAreaElement): void {
    const caret = target.selectionStart ?? target.value.length;
    const before = target.value.slice(0, caret);
    const match = before.match(/(?:^|\s)@([A-Za-z0-9_]{0,32})$/);
    if (match) {
        mentionTokenStart.value = caret - match[1].length - 1;
        mentionQuery.value = match[1];
        mentionOpen.value = true;
    } else {
        mentionOpen.value = false;
        mentionQuery.value = '';
        mentionTokenStart.value = -1;
    }
}

function applyMention(user: ChatUserRef): void {
    if (!textareaEl.value || mentionTokenStart.value < 0) {
        return;
    }
    const username = user.username ?? '';
    if (username === '') {
        mentionOpen.value = false;
        return;
    }
    const value = form.body;
    const caret =
        textareaEl.value.selectionStart ?? value.length;
    const replacement = `@${username} `;
    const next =
        value.slice(0, mentionTokenStart.value) +
        replacement +
        value.slice(caret);
    form.body = next;
    mentionOpen.value = false;
    nextTick(() => {
        if (!textareaEl.value) return;
        const pos = mentionTokenStart.value + replacement.length;
        textareaEl.value.focus();
        textareaEl.value.setSelectionRange(pos, pos);
    });
}

function onSubmit(): void {
    if (props.disabled || form.body.trim() === '') {
        return;
    }
    form.post(
        ChatMessageController.store({ room: props.roomId }).url,
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => form.reset('body'),
        },
    );
}

function onKeydown(event: KeyboardEvent): void {
    if (
        event.key === 'Enter' &&
        !event.shiftKey &&
        !mentionOpen.value
    ) {
        event.preventDefault();
        onSubmit();
    }
}
</script>

<template>
    <form
        class="relative border-t border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950"
        :style="{ paddingBottom: 'calc(0.75rem + env(safe-area-inset-bottom))' }"
        @submit.prevent="onSubmit"
    >
        <div
            v-if="mentionOpen"
            class="absolute bottom-full left-3 right-3 mb-2 max-h-48 overflow-y-auto rounded-md border border-zinc-200 bg-white shadow-md dark:border-zinc-800 dark:bg-zinc-900"
            role="listbox"
            :aria-label="t('chat.mentionSearch.label')"
        >
            <p
                v-if="!mentionLoading && mentionResults.length === 0"
                class="p-2 text-sm text-zinc-500"
            >
                {{ t('chat.mentionSearch.empty') }}
            </p>
            <button
                v-for="candidate in mentionResults"
                :key="candidate.id"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"
                @click="applyMention(candidate)"
            >
                <span class="font-medium">@{{ candidate.username }}</span>
                <span class="ml-2 text-zinc-500">{{ candidate.name }}</span>
            </button>
        </div>
        <div class="flex gap-2">
            <textarea
                ref="textareaEl"
                :value="form.body"
                :placeholder="placeholder"
                :disabled="disabled"
                rows="2"
                class="placeholder:text-muted-foreground dark:bg-input/30 border-input flex-1 min-w-0 resize-none rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                :aria-label="t('chat.composer.placeholder')"
                @input="onInput"
                @keydown="onKeydown"
            />
            <Button
                type="submit"
                :disabled="disabled || form.processing || form.body.trim() === ''"
            >
                {{ t('chat.composer.send') }}
            </Button>
        </div>
        <p
            v-if="form.errors.body"
            class="mt-1 text-sm text-red-600 dark:text-red-400"
        >
            {{ form.errors.body }}
        </p>
        <p
            v-else
            class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"
        >
            {{ t('chat.composer.hint') }}
        </p>
    </form>
</template>
