<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import ThemeController from '@/actions/App/Domain/Theme/Http/Controllers/ThemeController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as themesIndex } from '@/routes/themes';
import type { BreadcrumbItem } from '@/types';

type Theme = {
    id: number;
    name: string;
    description: string | null;
};

const props = defineProps<{
    themes: Theme[];
    defaultThemeId: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: themesIndex().url },
    { title: 'Themes', href: themesIndex().url },
];

const NONE_SENTINEL = '__none__';

const themeToDelete = ref<Theme | null>(null);
const defaultSelection = ref<string>(
    props.defaultThemeId === null
        ? NONE_SENTINEL
        : String(props.defaultThemeId),
);

function confirmDelete() {
    if (!themeToDelete.value) {
        return;
    }

    router.delete(ThemeController.destroy(themeToDelete.value.id).url, {
        onFinish: () => {
            themeToDelete.value = null;
        },
    });
}

function saveDefault() {
    router.patch(ThemeController.setDefault().url, {
        theme_id:
            defaultSelection.value === NONE_SENTINEL
                ? null
                : Number(defaultSelection.value),
    });
}
</script>

<template>
    <Head title="Themes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Theme Library</h2>
                    <p class="text-sm text-muted-foreground">
                        Color palettes assignable to events. Each palette ships
                        separate light and dark colors and overrides the
                        platform's default tokens.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="ThemeController.create().url">
                        <Plus class="size-4" />
                        Create Theme
                    </Link>
                </Button>
            </div>

            <div
                class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="text-sm font-medium">Site-wide default palette</div>
                <p class="text-xs text-muted-foreground">
                    Applied on all routes that don't have an event-specific
                    palette assigned. Leave unset to use the platform's default
                    appearance.
                </p>
                <div class="flex items-center gap-2">
                    <Select v-model="defaultSelection" class="max-w-xs">
                        <SelectTrigger class="max-w-xs">
                            <SelectValue
                                placeholder="Platform default (none)"
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE_SENTINEL">
                                Platform default (none)
                            </SelectItem>
                            <SelectItem
                                v-for="t in props.themes"
                                :key="t.id"
                                :value="String(t.id)"
                            >
                                {{ t.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Button @click="saveDefault">Save default</Button>
                </div>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="px-4">Name</TableHead>
                            <TableHead class="px-4">Description</TableHead>
                            <TableHead class="px-4">Status</TableHead>
                            <TableHead class="px-4 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="props.themes.length">
                            <TableRow
                                v-for="theme in props.themes"
                                :key="theme.id"
                            >
                                <TableCell class="px-4 py-3 font-medium">
                                    {{ theme.name }}
                                </TableCell>
                                <TableCell
                                    class="px-4 py-3 text-muted-foreground"
                                >
                                    {{ theme.description ?? '—' }}
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <Badge
                                        v-if="props.defaultThemeId === theme.id"
                                        variant="default"
                                    >
                                        Site default
                                    </Badge>
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                        >—</span
                                    >
                                </TableCell>
                                <TableCell class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    ThemeController.edit(
                                                        theme.id,
                                                    ).url
                                                "
                                            >
                                                <Pencil class="size-4" />
                                                Edit
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="themeToDelete = theme"
                                        >
                                            <Trash2
                                                class="size-4 text-destructive"
                                            />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty v-else :colspan="4">
                            No themes yet — create one to get started.
                        </TableEmpty>
                    </TableBody>
                </Table>
            </div>
        </div>

        <Dialog
            :open="themeToDelete !== null"
            @update:open="(o) => !o && (themeToDelete = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Delete theme “{{ themeToDelete?.name }}”?
                    </DialogTitle>
                    <DialogDescription>
                        Events currently assigned this theme will fall back to
                        the site-wide default (if set) or the platform's default
                        appearance. This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="themeToDelete = null">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
