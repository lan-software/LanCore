<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface PolicyType {
    id: number;
    key: string;
    label: string;
    description: string | null;
}

const props = defineProps<{
    open: boolean;
    policyTypes: PolicyType[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const { t } = useI18n();

const editing = ref<PolicyType | null>(null);
const newType = ref({ key: '', label: '', description: '' });
const submitting = ref(false);
const errors = ref<Record<string, string>>({});

function startEdit(type: PolicyType): void {
    editing.value = { ...type };
    errors.value = {};
}

function cancelEdit(): void {
    editing.value = null;
    errors.value = {};
}

function saveEdit(): void {
    if (!editing.value) {
        return;
    }

    submitting.value = true;
    router.put(
        `/admin/policies/types/${editing.value.id}`,
        {
            key: editing.value.key,
            label: editing.value.label,
            description: editing.value.description,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = null;
                errors.value = {};
            },
            onError: (e) => (errors.value = e as Record<string, string>),
            onFinish: () => (submitting.value = false),
        },
    );
}

function deleteType(type: PolicyType): void {
    if (
        !confirm(
            t('policies.admin.types.delete_confirm', { label: type.label }),
        )
    ) {
        return;
    }

    router.delete(`/admin/policies/types/${type.id}`, {
        preserveScroll: true,
    });
}

function createType(): void {
    submitting.value = true;
    router.post(
        '/admin/policies/types',
        {
            key: newType.value.key,
            label: newType.value.label,
            description: newType.value.description || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                newType.value = { key: '', label: '', description: '' };
                errors.value = {};
            },
            onError: (e) => (errors.value = e as Record<string, string>),
            onFinish: () => (submitting.value = false),
        },
    );
}

function close(): void {
    cancelEdit();
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="props.open" @update:open="(v) => !v && close()">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{
                    $t('policies.admin.types.title')
                }}</DialogTitle>
                <DialogDescription>
                    {{ $t('policies.admin.types.description') }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-2">
                    <h4 class="text-sm font-semibold">
                        {{ $t('policies.admin.types.existing') }}
                    </h4>
                    <p
                        v-if="props.policyTypes.length === 0"
                        class="text-sm text-muted-foreground italic"
                    >
                        {{ $t('policies.admin.types.no_types') }}
                    </p>
                    <ul v-else class="divide-y rounded-md border">
                        <li
                            v-for="type in props.policyTypes"
                            :key="type.id"
                            class="p-3"
                        >
                            <div
                                v-if="editing && editing.id === type.id"
                                class="grid gap-2"
                            >
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <Label :for="`edit-key-${type.id}`">
                                            {{ $t('policies.admin.types.key') }}
                                        </Label>
                                        <Input
                                            :id="`edit-key-${type.id}`"
                                            v-model="editing.key"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <Label :for="`edit-label-${type.id}`">
                                            {{
                                                $t('policies.admin.types.label')
                                            }}
                                        </Label>
                                        <Input
                                            :id="`edit-label-${type.id}`"
                                            v-model="editing.label"
                                            required
                                        />
                                    </div>
                                </div>
                                <div>
                                    <Label :for="`edit-desc-${type.id}`">
                                        {{
                                            $t(
                                                'policies.admin.types.description_optional',
                                            )
                                        }}
                                    </Label>
                                    <Textarea
                                        :id="`edit-desc-${type.id}`"
                                        v-model="editing.description as string"
                                        rows="2"
                                    />
                                </div>
                                <div class="flex gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        :disabled="submitting"
                                        @click="saveEdit"
                                    >
                                        {{ $t('policies.admin.types.save') }}
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        :disabled="submitting"
                                        @click="cancelEdit"
                                    >
                                        {{ $t('policies.admin.types.cancel') }}
                                    </Button>
                                </div>
                            </div>
                            <div
                                v-else
                                class="flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium">
                                        {{ type.label }}
                                    </div>
                                    <div
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ type.key }}
                                    </div>
                                    <div
                                        v-if="type.description"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ type.description }}
                                    </div>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        @click="startEdit(type)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        @click="deleteType(type)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="space-y-2 rounded-md border bg-muted/40 p-4">
                    <h4 class="text-sm font-semibold">
                        {{ $t('policies.admin.types.add_heading') }}
                    </h4>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <Label for="new-type-key">
                                {{ $t('policies.admin.types.key') }}
                            </Label>
                            <Input
                                id="new-type-key"
                                v-model="newType.key"
                                :placeholder="
                                    $t('policies.admin.types.key_placeholder')
                                "
                                required
                            />
                            <p
                                v-if="errors.key"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ errors.key }}
                            </p>
                        </div>
                        <div>
                            <Label for="new-type-label">
                                {{ $t('policies.admin.types.label') }}
                            </Label>
                            <Input
                                id="new-type-label"
                                v-model="newType.label"
                                :placeholder="
                                    $t('policies.admin.types.label_placeholder')
                                "
                                required
                            />
                            <p
                                v-if="errors.label"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ errors.label }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <Label for="new-type-description">
                            {{
                                $t('policies.admin.types.description_optional')
                            }}
                        </Label>
                        <Textarea
                            id="new-type-description"
                            v-model="newType.description"
                            rows="2"
                        />
                    </div>
                    <Button
                        type="button"
                        :disabled="submitting || !newType.key || !newType.label"
                        @click="createType"
                    >
                        {{ $t('policies.admin.types.add') }}
                    </Button>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="close">
                    {{ $t('common.close') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
