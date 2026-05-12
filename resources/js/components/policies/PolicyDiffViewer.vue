<script setup lang="ts">
// @see GitHub #10 — minified policy changelog diff with expand/collapse.
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface DiffRowDto {
    op: 'eq' | 'add' | 'del';
    line: string;
}

interface VisibleEntry {
    kind: 'row';
    op: 'eq' | 'add' | 'del';
    line: string;
}

interface SkipEntry {
    kind: 'skip';
    count: number;
}

const props = withDefaults(
    defineProps<{
        rows: DiffRowDto[];
        /**
         * Number of unchanged lines kept around each changed line in compact mode.
         * Mirrors the `unified` diff convention; 2–3 is the common sweet spot.
         */
        context?: number;
    }>(),
    { context: 2 },
);

const { t } = useI18n();

const expanded = ref(false);

// Indexes of any non-`eq` (changed) row in the input.
const changedIndices = computed<number[]>(() =>
    props.rows.reduce<number[]>((acc, row, index) => {
        if (row.op !== 'eq') acc.push(index);
        return acc;
    }, []),
);

const hasChanges = computed(() => changedIndices.value.length > 0);

// Set of row indexes to keep in compact mode: every changed row + `context`
// rows on either side. The complement of this set is collapsed into skip
// markers between visible hunks.
const visibleIndexSet = computed<Set<number>>(() => {
    const set = new Set<number>();
    const ctx = Math.max(0, props.context);
    for (const idx of changedIndices.value) {
        for (let k = idx - ctx; k <= idx + ctx; k++) {
            if (k >= 0 && k < props.rows.length) set.add(k);
        }
    }
    return set;
});

const compactView = computed<Array<VisibleEntry | SkipEntry>>(() => {
    const out: Array<VisibleEntry | SkipEntry> = [];
    let skipping = 0;
    for (let i = 0; i < props.rows.length; i++) {
        if (visibleIndexSet.value.has(i)) {
            if (skipping > 0) {
                out.push({ kind: 'skip', count: skipping });
                skipping = 0;
            }
            const row = props.rows[i];
            out.push({ kind: 'row', op: row.op, line: row.line });
        } else {
            skipping += 1;
        }
    }
    if (skipping > 0) out.push({ kind: 'skip', count: skipping });
    return out;
});

function rowClass(op: 'eq' | 'add' | 'del'): string {
    if (op === 'add') {
        return 'bg-green-50 px-2 py-0.5 text-green-900 dark:bg-green-950/30 dark:text-green-200';
    }
    if (op === 'del') {
        return 'bg-red-50 px-2 py-0.5 text-red-900 line-through dark:bg-red-950/30 dark:text-red-200';
    }
    return 'px-2 py-0.5 text-muted-foreground';
}

function rowPrefix(op: 'eq' | 'add' | 'del'): string {
    if (op === 'add') return '+ ';
    if (op === 'del') return '- ';
    return '  ';
}
</script>

<template>
    <div class="font-mono text-xs">
        <div
            v-if="hasChanges"
            class="flex items-center justify-end border-b bg-muted/20 px-2 py-1"
        >
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-sm px-1.5 py-0.5 text-xs font-medium text-muted-foreground hover:text-foreground"
                @click="expanded = !expanded"
            >
                <component
                    :is="expanded ? ChevronUp : ChevronDown"
                    class="size-3"
                />
                {{
                    expanded
                        ? t('policies.admin.show.diff_collapse')
                        : t('policies.admin.show.diff_expand')
                }}
            </button>
        </div>

        <div class="max-h-96 overflow-auto p-2">
            <!-- Full diff -->
            <template v-if="expanded">
                <div
                    v-for="(row, idx) in rows"
                    :key="`full-${idx}`"
                    :class="rowClass(row.op)"
                >
                    <span aria-hidden="true">{{ rowPrefix(row.op) }}</span>{{
                        row.line
                    }}<span v-if="row.line === ''">&nbsp;</span>
                </div>
            </template>
            <!-- Compact diff: changed lines + minimal context, skipped runs collapsed -->
            <template v-else>
                <template
                    v-for="(entry, idx) in compactView"
                    :key="`compact-${idx}`"
                >
                    <div
                        v-if="entry.kind === 'row'"
                        :class="rowClass(entry.op)"
                    >
                        <span aria-hidden="true">{{ rowPrefix(entry.op) }}</span>{{
                            entry.line
                        }}<span v-if="entry.line === ''">&nbsp;</span>
                    </div>
                    <button
                        v-else
                        type="button"
                        class="block w-full px-2 py-1 text-left text-xs text-muted-foreground/70 italic hover:bg-muted/30 hover:text-muted-foreground"
                        @click="expanded = true"
                    >
                        {{
                            t('policies.admin.show.diff_hidden_lines', {
                                count: entry.count,
                            })
                        }}
                    </button>
                </template>
            </template>
        </div>
    </div>
</template>
