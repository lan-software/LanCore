<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, RotateCcw, Save, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import NonEditorialChangeConfirmDialog from '@/components/policies/NonEditorialChangeConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface PolicyVersionRow {
    id: number;
    version_number: number;
    locale: string;
    is_non_editorial_change: boolean;
    public_statement: string | null;
    effective_at: string;
    published_at: string;
    pdf_path: string | null;
    published_by: { id: number; name: string } | null;
}

interface DraftRow {
    locale: string;
    content: string;
    updated_at: string | null;
    updated_by: { id: number; name: string } | null;
}

interface AuditRow {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: number;
    actor: { id: number; name: string } | null;
    old_values: Record<string, unknown> | string | null;
    new_values: Record<string, unknown> | string | null;
    created_at: string;
}

interface DiffRow {
    from_version: number;
    to_version: number;
    locale: string;
    html: string;
}

const props = defineProps<{
    policy: {
        id: number;
        key: string;
        name: string;
        description: string | null;
        is_required_for_registration: boolean;
        archived_at: string | null;
        required_acceptance_version_number: number | null;
        type: { label: string } | null;
        versions: PolicyVersionRow[];
        drafts: DraftRow[];
        next_version_number: number;
    };
    priorAcceptorCount: number;
    audits: AuditRow[];
    diffs: DiffRow[];
}>();

const { t } = useI18n();
const page = usePage();

const availableLocales = computed(
    () => (page.props.availableLocales as string[]) ?? [],
);

const localesWithDraft = computed(() =>
    props.policy.drafts.map((d) => d.locale),
);

const localesAvailableToAdd = computed(() =>
    availableLocales.value.filter((l) => !localesWithDraft.value.includes(l)),
);

const newLocale = ref<string>('');
const savingLocale = ref<string | null>(null);
const editorContent = reactive<Record<string, string>>({});

watch(
    () => props.policy.drafts,
    (drafts) => {
        for (const draft of drafts) {
            if (editorContent[draft.locale] === undefined) {
                editorContent[draft.locale] = draft.content;
            }
        }
        for (const locale of Object.keys(editorContent)) {
            if (!drafts.some((d) => d.locale === locale)) {
                delete editorContent[locale];
            }
        }
    },
    { immediate: true, deep: true },
);

function isDirty(locale: string): boolean {
    const draft = props.policy.drafts.find((d) => d.locale === locale);
    if (!draft) {
        return false;
    }

    return editorContent[locale] !== draft.content;
}

function saveDraft(locale: string): void {
    savingLocale.value = locale;
    router.put(
        `/admin/policies/${props.policy.id}/drafts/${locale}`,
        { content: editorContent[locale] ?? '' },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                savingLocale.value = null;
            },
        },
    );
}

function discardDraft(locale: string): void {
    const draft = props.policy.drafts.find((d) => d.locale === locale);
    if (draft) {
        editorContent[locale] = draft.content;
    }
}

function removeLocale(locale: string): void {
    router.delete(`/admin/policies/${props.policy.id}/drafts/${locale}`, {
        preserveScroll: true,
    });
}

function addLocale(): void {
    if (!newLocale.value) {
        return;
    }
    router.post(
        `/admin/policies/${props.policy.id}/drafts`,
        { locale: newLocale.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                newLocale.value = '';
            },
        },
    );
}

const allDraftsHaveContent = computed(
    () =>
        props.policy.drafts.length > 0 &&
        props.policy.drafts.every((d) => (d.content ?? '').trim() !== ''),
);

const publishForm = useForm({
    is_non_editorial_change: false as boolean,
    public_statement: '',
    effective_at: '',
});

const dialogOpen = ref(false);

function attemptPublish(): void {
    if (!allDraftsHaveContent.value) {
        return;
    }

    if (publishForm.is_non_editorial_change) {
        dialogOpen.value = true;
        return;
    }

    publishNow();
}

function publishNow(): void {
    publishForm.post(`/admin/policies/${props.policy.id}/versions`);
}

function eventLabel(event: string): string {
    const known = ['created', 'updated', 'restored', 'deleted'];

    if (known.includes(event)) {
        return t(`policies.admin.show.audit_event_${event}`);
    }

    return event;
}

function formatChanges(values: AuditRow['new_values']): string {
    if (!values || typeof values === 'string') {
        return '';
    }

    return Object.keys(values).join(', ');
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('policies.admin.index.title'), href: '/admin/policies' },
    { title: props.policy.name, href: `/admin/policies/${props.policy.id}` },
];
</script>

<template>
    <Head :title="policy.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <Heading
                        :title="policy.name"
                        :description="policy.description ?? ''"
                    />
                    <div
                        class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                    >
                        <span>
                            {{ $t('policies.admin.show.key_label') }}:
                            <code>{{ policy.key }}</code>
                        </span>
                        <span v-if="policy.type"
                            >· {{ policy.type.label }}</span
                        >
                        <Badge v-if="policy.is_required_for_registration">
                            {{
                                $t(
                                    'policies.admin.show.required_for_registration_badge',
                                )
                            }}
                        </Badge>
                        <Badge v-if="policy.archived_at" variant="secondary">
                            {{ $t('policies.admin.show.archived_badge') }}
                        </Badge>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/admin/policies/${policy.id}/edit`">
                        <Button variant="outline">
                            <Pencil class="size-4" />
                            {{ $t('policies.admin.show.edit_metadata') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <section class="rounded-md border">
                <div
                    class="flex items-center justify-between border-b bg-muted/40 px-4 py-2"
                >
                    <div>
                        <h3 class="text-sm font-semibold">
                            {{
                                $t('policies.admin.show.drafts_heading', {
                                    version: policy.next_version_number,
                                })
                            }}
                        </h3>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{
                                $t('policies.admin.show.drafts_description', {
                                    version: policy.next_version_number,
                                })
                            }}
                        </p>
                    </div>
                </div>

                <div class="space-y-6 p-4">
                    <p
                        v-if="policy.drafts.length === 0"
                        class="text-sm text-muted-foreground italic"
                    >
                        {{ $t('policies.admin.show.drafts_empty') }}
                    </p>

                    <div
                        v-for="draft in policy.drafts"
                        :key="draft.locale"
                        class="space-y-2 rounded-md border p-3"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <div class="flex items-center gap-2">
                                <Badge variant="secondary">{{
                                    draft.locale
                                }}</Badge>
                                <span
                                    v-if="
                                        editorContent[draft.locale] !==
                                            undefined &&
                                        editorContent[draft.locale].trim() ===
                                            ''
                                    "
                                    class="text-xs text-amber-700 dark:text-amber-400"
                                >
                                    {{
                                        $t(
                                            'policies.admin.show.draft_content_empty_warning',
                                        )
                                    }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :disabled="
                                        !isDirty(draft.locale) ||
                                        savingLocale === draft.locale
                                    "
                                    @click="discardDraft(draft.locale)"
                                >
                                    <RotateCcw class="size-4" />
                                    {{ $t('policies.admin.show.draft_discard') }}
                                </Button>
                                <Button
                                    size="sm"
                                    :disabled="savingLocale === draft.locale"
                                    @click="saveDraft(draft.locale)"
                                >
                                    <Save class="size-4" />
                                    {{
                                        savingLocale === draft.locale
                                            ? $t(
                                                  'policies.admin.show.draft_saving',
                                              )
                                            : $t(
                                                  'policies.admin.show.draft_save',
                                              )
                                    }}
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :disabled="policy.drafts.length <= 1"
                                    @click="removeLocale(draft.locale)"
                                >
                                    <Trash2 class="size-4" />
                                    {{
                                        $t(
                                            'policies.admin.show.draft_remove_locale',
                                        )
                                    }}
                                </Button>
                            </div>
                        </div>

                        <Textarea
                            v-model="editorContent[draft.locale]"
                            rows="14"
                            class="font-mono text-sm"
                            :placeholder="
                                $t(
                                    'policies.admin.show.draft_content_placeholder',
                                )
                            "
                        />

                        <p class="text-xs text-muted-foreground">
                            <template v-if="draft.updated_at">
                                {{
                                    $t(
                                        'policies.admin.show.draft_last_edited_at',
                                        {
                                            at: new Date(
                                                draft.updated_at,
                                            ).toLocaleString(),
                                        },
                                    )
                                }}
                                <template v-if="draft.updated_by">
                                    ·
                                    {{
                                        $t(
                                            'policies.admin.show.draft_last_edited_by',
                                            { name: draft.updated_by.name },
                                        )
                                    }}
                                </template>
                            </template>
                            <template v-else>
                                {{
                                    $t('policies.admin.show.draft_never_edited')
                                }}
                            </template>
                        </p>
                    </div>

                    <div
                        v-if="localesAvailableToAdd.length > 0"
                        class="flex items-end gap-2 border-t pt-4"
                    >
                        <div class="grid gap-1">
                            <Label for="add-locale">
                                {{ $t('policies.admin.show.add_locale') }}
                            </Label>
                            <Select
                                id="add-locale"
                                v-model="newLocale"
                                class="w-40"
                            >
                                <SelectTrigger class="w-40">
                                    <SelectValue
                                        :placeholder="
                                            $t(
                                                'policies.admin.show.add_locale_placeholder',
                                            )
                                        "
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="locale in localesAvailableToAdd"
                                        :key="locale"
                                        :value="locale"
                                    >
                                        {{ locale }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            :disabled="!newLocale"
                            variant="outline"
                            @click="addLocale"
                        >
                            <Plus class="size-4" />
                            {{ $t('policies.admin.show.add_locale') }}
                        </Button>
                    </div>
                </div>
            </section>

            <form
                class="space-y-4 rounded-md border p-4"
                @submit.prevent="attemptPublish"
            >
                <div class="grid gap-2">
                    <Label for="effective_at">
                        {{
                            $t(
                                'policies.admin.show.publish_dialog.effective_at',
                            )
                        }}
                    </Label>
                    <Input
                        id="effective_at"
                        v-model="publishForm.effective_at"
                        type="datetime-local"
                        class="w-full max-w-sm"
                    />
                </div>

                <div
                    class="rounded-md border border-amber-300 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950/30"
                >
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_non_editorial_change"
                            v-model="publishForm.is_non_editorial_change"
                        />
                        <Label
                            for="is_non_editorial_change"
                            class="cursor-pointer font-medium"
                        >
                            {{
                                $t(
                                    'policies.admin.show.publish_dialog.is_non_editorial',
                                )
                            }}
                        </Label>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{
                            $t(
                                'policies.admin.show.publish_dialog.is_non_editorial_help',
                            )
                        }}
                    </p>
                </div>

                <div
                    v-if="publishForm.is_non_editorial_change"
                    class="grid gap-2"
                >
                    <Label for="public_statement">
                        {{
                            $t(
                                'policies.admin.show.publish_dialog.public_statement',
                            )
                        }}
                    </Label>
                    <Textarea
                        id="public_statement"
                        v-model="publishForm.public_statement"
                        rows="4"
                        :placeholder="
                            $t(
                                'policies.admin.show.publish_dialog.public_statement_placeholder',
                            )
                        "
                    />
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        type="submit"
                        :disabled="
                            !allDraftsHaveContent || publishForm.processing
                        "
                    >
                        <Plus class="size-4" />
                        {{
                            publishForm.processing
                                ? $t(
                                      'policies.admin.show.publish_dialog.publishing',
                                  )
                                : $t(
                                      'policies.admin.show.publish_new_version',
                                      { version: policy.next_version_number },
                                  )
                        }}
                    </Button>
                    <p
                        v-if="!allDraftsHaveContent"
                        class="text-xs text-muted-foreground"
                    >
                        {{
                            $t(
                                'policies.admin.show.publish_disabled_empty_drafts',
                            )
                        }}
                    </p>
                </div>
            </form>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                {{ $t('policies.admin.show.col_version') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_locale') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_type') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_published') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_by') }}
                            </TableHead>
                            <TableHead>
                                {{ $t('policies.admin.show.col_pdf') }}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="version in policy.versions"
                            :key="version.id"
                            :class="{
                                'bg-amber-50 dark:bg-amber-950/30':
                                    policy.required_acceptance_version_number ===
                                    version.version_number,
                            }"
                        >
                            <TableCell class="font-medium">
                                v{{ version.version_number }}
                                <Badge
                                    v-if="
                                        policy.required_acceptance_version_number ===
                                        version.version_number
                                    "
                                    class="ml-2"
                                >
                                    {{
                                        $t(
                                            'policies.admin.show.requires_acceptance_badge',
                                        )
                                    }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ version.locale }}</TableCell>
                            <TableCell>
                                <Badge
                                    v-if="version.is_non_editorial_change"
                                    variant="destructive"
                                >
                                    {{
                                        $t(
                                            'policies.admin.show.non_editorial_badge',
                                        )
                                    }}
                                </Badge>
                                <Badge v-else variant="secondary">
                                    {{
                                        $t(
                                            'policies.admin.show.editorial_badge',
                                        )
                                    }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                {{
                                    new Date(
                                        version.published_at,
                                    ).toLocaleDateString()
                                }}
                            </TableCell>
                            <TableCell>
                                {{ version.published_by?.name ?? '—' }}
                            </TableCell>
                            <TableCell>
                                <span
                                    v-if="version.pdf_path"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ version.pdf_path }}
                                </span>
                                <span v-else class="text-muted-foreground">
                                    —
                                </span>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="policy.versions.length === 0">
                            <TableCell
                                :colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                {{ $t('policies.admin.show.no_versions_yet') }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <section v-if="diffs.length > 0" class="space-y-4">
                <div
                    v-for="diff in diffs"
                    :key="`${diff.locale}-${diff.from_version}-${diff.to_version}`"
                    class="rounded-md border"
                >
                    <div
                        class="flex items-center justify-between border-b bg-muted/40 px-4 py-2"
                    >
                        <h3 class="text-sm font-semibold">
                            {{
                                $t('policies.admin.show.diff_heading', {
                                    from: diff.from_version,
                                    to: diff.to_version,
                                })
                            }}
                        </h3>
                        <span class="text-xs text-muted-foreground">
                            {{ diff.locale }}
                        </span>
                    </div>
                    <div
                        v-if="diff.html.trim()"
                        class="max-h-96 overflow-auto p-2 font-mono text-xs"
                        v-html="diff.html"
                    />
                    <p v-else class="p-4 text-sm text-muted-foreground italic">
                        {{ $t('policies.admin.show.diff_empty') }}
                    </p>
                </div>
            </section>

            <section class="rounded-md border">
                <h3
                    class="border-b bg-muted/40 px-4 py-2 text-sm font-semibold"
                >
                    {{ $t('policies.admin.show.audit_log_heading') }}
                </h3>
                <ul v-if="audits.length > 0" class="divide-y text-sm">
                    <li
                        v-for="audit in audits"
                        :key="audit.id"
                        class="px-4 py-2"
                    >
                        <div class="flex flex-wrap items-baseline gap-2">
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{
                                    new Date(audit.created_at).toLocaleString()
                                }}
                            </span>
                            <span class="font-medium">
                                {{
                                    audit.actor?.name ??
                                    $t(
                                        'policies.admin.show.audit_actor_unknown',
                                    )
                                }}
                            </span>
                            <span class="text-muted-foreground">
                                {{ eventLabel(audit.event) }}
                                {{ audit.auditable_type }}#{{
                                    audit.auditable_id
                                }}
                            </span>
                        </div>
                        <p
                            v-if="formatChanges(audit.new_values)"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ formatChanges(audit.new_values) }}
                        </p>
                    </li>
                </ul>
                <p
                    v-else
                    class="px-4 py-6 text-sm text-muted-foreground italic"
                >
                    {{ $t('policies.admin.show.audit_log_empty') }}
                </p>
            </section>

            <NonEditorialChangeConfirmDialog
                v-model:open="dialogOpen"
                :prior-acceptor-count="priorAcceptorCount"
                @confirmed="publishNow"
            />
        </div>
    </AppLayout>
</template>
