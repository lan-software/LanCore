<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type RuleConfig = {
    ticket_type_ids?: number[];
    addon_ids?: number[];
};

export type RuleLeaf = {
    type: 'rule';
    key: string;
    config?: RuleConfig;
};

export type RuleGroup = {
    type: 'all' | 'any';
    rules: Array<RuleGroup | RuleLeaf>;
};

const { t } = useI18n();

const props = defineProps<{
    modelValue: RuleGroup | null;
    ruleTypes: string[];
    ticketTypes: { id: number; name: string }[];
    addons: { id: number; name: string }[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: RuleGroup | null];
}>();

const tree = computed<RuleGroup>(
    () => props.modelValue ?? { type: 'all', rules: [] },
);

function update() {
    const next = tree.value;
    const isEmpty = next.rules.length === 0;
    emit('update:modelValue', isEmpty ? null : structuredClone(next));
}

function toggleRoot() {
    tree.value.type = tree.value.type === 'all' ? 'any' : 'all';
    update();
}

function addRule(group: RuleGroup) {
    group.rules.push({
        type: 'rule',
        key: props.ruleTypes[0] ?? 'has_steam_account_linked',
        config: {},
    });
    update();
}

function addSubgroup(group: RuleGroup) {
    group.rules.push({ type: 'any', rules: [] });
    update();
}

function removeAt(group: RuleGroup, index: number) {
    group.rules.splice(index, 1);
    update();
}

function setRuleKey(leaf: RuleLeaf, key: string) {
    leaf.key = key;
    leaf.config = {};
    update();
}

function toggleSubgroupType(group: RuleGroup) {
    group.type = group.type === 'all' ? 'any' : 'all';
    update();
}

function isTicketSelected(leaf: RuleLeaf, id: number): boolean {
    return (leaf.config?.ticket_type_ids ?? []).includes(id);
}

function toggleTicket(leaf: RuleLeaf, id: number) {
    const config = (leaf.config ??= {});
    const ids = config.ticket_type_ids ?? [];
    config.ticket_type_ids = ids.includes(id)
        ? ids.filter((v) => v !== id)
        : [...ids, id];
    update();
}

function isAddonSelected(leaf: RuleLeaf, id: number): boolean {
    return (leaf.config?.addon_ids ?? []).includes(id);
}

function toggleAddon(leaf: RuleLeaf, id: number) {
    const config = (leaf.config ??= {});
    const ids = config.addon_ids ?? [];
    config.addon_ids = ids.includes(id)
        ? ids.filter((v) => v !== id)
        : [...ids, id];
    update();
}

function isGroup(
    node: RuleGroup | RuleLeaf,
): node is RuleGroup {
    return node.type === 'all' || node.type === 'any';
}

function asLeaf(node: RuleGroup | RuleLeaf): RuleLeaf {
    return node as RuleLeaf;
}

function asGroup(node: RuleGroup | RuleLeaf): RuleGroup {
    return node as RuleGroup;
}
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-3 dark:border-sidebar-border">
        <div class="mb-2 flex items-center justify-between gap-2">
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded border border-input bg-background px-2 py-0.5 text-xs font-semibold uppercase tracking-wide hover:bg-accent disabled:opacity-50"
                :disabled="disabled"
                @click="toggleRoot"
            >
                {{
                    tree.type === 'all'
                        ? t('competitions.signupRules.groupAll')
                        : t('competitions.signupRules.groupAny')
                }}
            </button>
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="rounded border border-input bg-background px-2 py-0.5 text-xs hover:bg-accent disabled:opacity-50"
                    :disabled="disabled"
                    @click="addRule(tree)"
                >
                    + {{ t('competitions.signupRules.addRule') }}
                </button>
                <button
                    type="button"
                    class="rounded border border-input bg-background px-2 py-0.5 text-xs hover:bg-accent disabled:opacity-50"
                    :disabled="disabled"
                    @click="addSubgroup(tree)"
                >
                    + {{ t('competitions.signupRules.addGroup') }}
                </button>
            </div>
        </div>

        <p
            v-if="tree.rules.length === 0"
            class="text-xs italic text-muted-foreground"
        >
            {{ t('competitions.signupRules.noRules') }}
        </p>

        <div v-else class="space-y-2">
            <div
                v-for="(node, i) in tree.rules"
                :key="i"
                class="rounded border bg-background p-2"
            >
                <!-- Subgroup -->
                <div v-if="isGroup(node)" class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded border border-input bg-background px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide hover:bg-accent disabled:opacity-50"
                            :disabled="disabled"
                            @click="toggleSubgroupType(asGroup(node))"
                        >
                            {{
                                asGroup(node).type === 'all'
                                    ? t('competitions.signupRules.groupAll')
                                    : t('competitions.signupRules.groupAny')
                            }}
                        </button>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="rounded border border-input bg-background px-2 py-0.5 text-[11px] hover:bg-accent disabled:opacity-50"
                                :disabled="disabled"
                                @click="addRule(asGroup(node))"
                            >
                                + {{ t('competitions.signupRules.addRule') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center justify-center rounded border border-input bg-background p-1 text-muted-foreground hover:bg-destructive hover:text-destructive-foreground disabled:opacity-50"
                                :disabled="disabled"
                                @click="removeAt(tree, i)"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="asGroup(node).rules.length === 0"
                        class="ml-2 text-[11px] italic text-muted-foreground"
                    >
                        —
                    </div>
                    <div
                        v-for="(inner, j) in asGroup(node).rules"
                        v-else
                        :key="j"
                        class="ml-2 flex items-start gap-2 rounded border bg-card/40 p-2"
                    >
                        <div class="flex-1 space-y-2">
                            <select
                                class="w-full rounded border border-input bg-background px-2 py-1 text-xs disabled:opacity-50"
                                :disabled="disabled || isGroup(inner)"
                                :value="
                                    isGroup(inner) ? '' : asLeaf(inner).key
                                "
                                @change="
                                    setRuleKey(
                                        asLeaf(inner),
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                            >
                                <option
                                    v-for="key in ruleTypes"
                                    :key="key"
                                    :value="key"
                                >
                                    {{
                                        t(
                                            'competitions.signupRules.ruleTypes.' +
                                                key,
                                        )
                                    }}
                                </option>
                            </select>

                            <div
                                v-if="
                                    !isGroup(inner) &&
                                    asLeaf(inner).key === 'has_ticket_of_type'
                                "
                                class="flex flex-wrap gap-1"
                            >
                                <label
                                    v-for="tt in ticketTypes"
                                    :key="tt.id"
                                    class="cursor-pointer rounded border px-2 py-0.5 text-[11px]"
                                    :class="
                                        isTicketSelected(asLeaf(inner), tt.id)
                                            ? 'border-primary bg-primary/10'
                                            : 'border-input'
                                    "
                                >
                                    <input
                                        type="checkbox"
                                        class="hidden"
                                        :checked="
                                            isTicketSelected(asLeaf(inner), tt.id)
                                        "
                                        @change="
                                            toggleTicket(asLeaf(inner), tt.id)
                                        "
                                    />
                                    {{ tt.name }}
                                </label>
                            </div>

                            <div
                                v-if="
                                    !isGroup(inner) &&
                                    asLeaf(inner).key === 'has_addon'
                                "
                                class="flex flex-wrap gap-1"
                            >
                                <label
                                    v-for="a in addons"
                                    :key="a.id"
                                    class="cursor-pointer rounded border px-2 py-0.5 text-[11px]"
                                    :class="
                                        isAddonSelected(asLeaf(inner), a.id)
                                            ? 'border-primary bg-primary/10'
                                            : 'border-input'
                                    "
                                >
                                    <input
                                        type="checkbox"
                                        class="hidden"
                                        :checked="
                                            isAddonSelected(asLeaf(inner), a.id)
                                        "
                                        @change="
                                            toggleAddon(asLeaf(inner), a.id)
                                        "
                                    />
                                    {{ a.name }}
                                </label>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="mt-0.5 inline-flex shrink-0 items-center justify-center rounded border border-input bg-background p-1 text-muted-foreground hover:bg-destructive hover:text-destructive-foreground disabled:opacity-50"
                            :disabled="disabled"
                            @click="removeAt(asGroup(node), j)"
                        >
                            ×
                        </button>
                    </div>
                </div>

                <!-- Leaf rule -->
                <div v-else class="flex items-start gap-2">
                    <div class="flex-1 space-y-2">
                        <select
                            class="w-full rounded border border-input bg-background px-2 py-1 text-xs disabled:opacity-50"
                            :disabled="disabled"
                            :value="asLeaf(node).key"
                            @change="
                                setRuleKey(
                                    asLeaf(node),
                                    ($event.target as HTMLSelectElement).value,
                                )
                            "
                        >
                            <option
                                v-for="key in ruleTypes"
                                :key="key"
                                :value="key"
                            >
                                {{
                                    t(
                                        'competitions.signupRules.ruleTypes.' +
                                            key,
                                    )
                                }}
                            </option>
                        </select>

                        <div
                            v-if="asLeaf(node).key === 'has_ticket_of_type'"
                            class="flex flex-wrap gap-1"
                        >
                            <label
                                v-for="tt in ticketTypes"
                                :key="tt.id"
                                class="cursor-pointer rounded border px-2 py-0.5 text-[11px]"
                                :class="
                                    isTicketSelected(asLeaf(node), tt.id)
                                        ? 'border-primary bg-primary/10'
                                        : 'border-input'
                                "
                            >
                                <input
                                    type="checkbox"
                                    class="hidden"
                                    :checked="
                                        isTicketSelected(asLeaf(node), tt.id)
                                    "
                                    @change="toggleTicket(asLeaf(node), tt.id)"
                                />
                                {{ tt.name }}
                            </label>
                        </div>

                        <div
                            v-if="asLeaf(node).key === 'has_addon'"
                            class="flex flex-wrap gap-1"
                        >
                            <label
                                v-for="a in addons"
                                :key="a.id"
                                class="cursor-pointer rounded border px-2 py-0.5 text-[11px]"
                                :class="
                                    isAddonSelected(asLeaf(node), a.id)
                                        ? 'border-primary bg-primary/10'
                                        : 'border-input'
                                "
                            >
                                <input
                                    type="checkbox"
                                    class="hidden"
                                    :checked="
                                        isAddonSelected(asLeaf(node), a.id)
                                    "
                                    @change="toggleAddon(asLeaf(node), a.id)"
                                />
                                {{ a.name }}
                            </label>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-0.5 inline-flex shrink-0 items-center justify-center rounded border border-input bg-background p-1 text-muted-foreground hover:bg-destructive hover:text-destructive-foreground disabled:opacity-50"
                        :disabled="disabled"
                        @click="removeAt(tree, i)"
                    >
                        ×
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
