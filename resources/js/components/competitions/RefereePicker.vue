<script setup lang="ts">
import { Minus, Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

/**
 * Compact user picker: a search box that filters the candidate list, with
 * +/- buttons to add or remove a user from the selection. The selected set
 * is submitted as hidden `referee_ids[]` inputs so it works inside a plain
 * Inertia `<Form>`.
 *
 * @see docs/mil-std-498/SRS.md COMP-REF-001
 */
interface UserRef {
    id: string;
    name: string;
    email: string | null;
    username?: string | null;
}

const props = defineProps<{
    users: UserRef[];
    initialSelectedIds: string[];
    name?: string;
    disabled?: boolean;
}>();

const { t } = useI18n();

const fieldName = computed(() => (props.name ?? 'referee_ids') + '[]');
const search = ref('');
const selectedIds = ref<Set<string>>(new Set(props.initialSelectedIds));

const byId = computed(() => {
    const m = new Map<string, UserRef>();

    for (const u of props.users) {
        m.set(u.id, u);
    }

    return m;
});

const filteredCandidates = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.users
        .filter((u) => !selectedIds.value.has(u.id))
        .filter((u) => {
            if (q === '') {
                return true;
            }

            return (
                u.name.toLowerCase().includes(q) ||
                (u.username ?? '').toLowerCase().includes(q) ||
                (u.email ?? '').toLowerCase().includes(q)
            );
        })
        .slice(0, 25);
});

const selectedUsers = computed(() =>
    Array.from(selectedIds.value)
        .map((id) => byId.value.get(id))
        .filter((u): u is UserRef => u !== undefined),
);

function add(id: string) {
    selectedIds.value = new Set([...selectedIds.value, id]);
}

function remove(id: string) {
    const next = new Set(selectedIds.value);
    next.delete(id);
    selectedIds.value = next;
}
</script>

<template>
    <div class="grid gap-2">
        <!--
            Sentinel hidden field so `$request->has('referee_ids')` is true even
            when the user removed all referees. The controller filters out empty
            string IDs before syncing the pivot, so this stays as a no-op when
            real selections exist alongside it.
        -->
        <input type="hidden" :name="fieldName" value="" />

        <!-- Selected referees list with remove buttons. -->
        <div v-if="selectedUsers.length > 0" class="flex flex-wrap gap-2">
            <span
                v-for="user in selectedUsers"
                :key="user.id"
                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
            >
                {{ user.name }}
                <button
                    v-if="!disabled"
                    type="button"
                    class="rounded-full p-0.5 hover:bg-amber-200 dark:hover:bg-amber-900"
                    :aria-label="t('common.remove')"
                    @click="remove(user.id)"
                >
                    <Minus class="size-3" />
                </button>
                <input type="hidden" :name="fieldName" :value="user.id" />
            </span>
        </div>

        <!-- Search-and-add box. -->
        <div class="relative">
            <Search
                class="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                type="search"
                :placeholder="t('games.refereesPlaceholder')"
                class="pl-8"
                :disabled="disabled"
            />
        </div>

        <!-- Filtered candidates with add button. Empty placeholder kept brief
             so the form stays compact even on competitions with many users. -->
        <ul
            v-if="filteredCandidates.length > 0"
            class="max-h-48 divide-y divide-zinc-100 overflow-y-auto rounded border dark:divide-zinc-800"
        >
            <li
                v-for="user in filteredCandidates"
                :key="user.id"
                class="flex items-center justify-between gap-2 px-3 py-1.5 text-sm"
            >
                <span class="truncate">
                    {{ user.name }}
                    <span
                        v-if="user.email"
                        class="text-xs text-muted-foreground"
                        >&lt;{{ user.email }}&gt;</span
                    >
                </span>
                <Button
                    v-if="!disabled"
                    type="button"
                    variant="ghost"
                    size="sm"
                    :aria-label="t('common.add')"
                    @click="add(user.id)"
                >
                    <Plus class="size-4" />
                </Button>
            </li>
        </ul>
        <p v-else-if="search !== ''" class="text-xs text-muted-foreground">
            {{ t('common.no_results') }}
        </p>
    </div>
</template>
