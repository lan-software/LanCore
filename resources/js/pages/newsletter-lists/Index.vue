<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, RefreshCw, Trash2, UsersRound } from 'lucide-vue-next';
import { ref } from 'vue';
import NewsletterListController from '@/actions/App/Domain/Newsletter/Http/Controllers/Admin/NewsletterListController';
import NewsletterListSyncController from '@/actions/App/Domain/Newsletter/Http/Controllers/Admin/NewsletterListSyncController';
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
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as newsletterListsIndex } from '@/routes/newsletter-lists';
import type { BreadcrumbItem } from '@/types';

type NewsletterList = {
    id: number;
    listmonk_id: number;
    name: string;
    description: string | null;
    type: string;
    optin: string;
    tags: string[] | null;
    is_user_selectable: boolean;
    is_default_public: boolean;
    last_synced_at: string | null;
};

const props = defineProps<{
    lists: NewsletterList[];
    listmonkEnabled: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: newsletterListsIndex().url },
    { title: 'Newsletter Lists', href: newsletterListsIndex().url },
];

const listToDelete = ref<NewsletterList | null>(null);
const optInTarget = ref<NewsletterList | null>(null);
const optInProcessing = ref(false);
const fetchProcessing = ref(false);

const page = usePage<{
    flash?: { status?: string | null; error?: string | null };
}>();

function confirmDelete() {
    if (!listToDelete.value) {
        return;
    }

    router.delete(NewsletterListController.destroy(listToDelete.value.id).url, {
        onFinish: () => {
            listToDelete.value = null;
        },
    });
}

function fetchFromListmonk() {
    fetchProcessing.value = true;
    router.post(
        NewsletterListSyncController.fetch().url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                fetchProcessing.value = false;
            },
        },
    );
}

function confirmOptInAll() {
    if (!optInTarget.value) {
        return;
    }

    optInProcessing.value = true;
    router.post(
        NewsletterListSyncController.optInAllUsers(optInTarget.value.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                optInProcessing.value = false;
                optInTarget.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Newsletter Lists" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                v-if="page.props.flash?.status"
                class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
            >
                {{ page.props.flash.status }}
            </div>
            <div
                v-if="page.props.flash?.error"
                class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
            >
                {{ page.props.flash.error }}
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Newsletter Lists</h2>
                    <p class="text-sm text-muted-foreground">
                        Mirror of your Listmonk lists. Mark a list as user
                        selectable to expose it on the per-user E-Mail Settings
                        page, and pick exactly one default public list for the
                        countdown signup form.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        :disabled="!listmonkEnabled || fetchProcessing"
                        @click="fetchFromListmonk"
                    >
                        <RefreshCw class="size-4" />
                        {{
                            fetchProcessing
                                ? 'Fetching…'
                                : 'Fetch from Listmonk'
                        }}
                    </Button>
                    <Button as-child>
                        <Link :href="NewsletterListController.create().url">
                            <Plus class="size-4" />
                            Create List
                        </Link>
                    </Button>
                </div>
            </div>

            <div
                v-if="!listmonkEnabled"
                class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
            >
                Listmonk integration is disabled. Set
                <code class="rounded bg-muted px-1">LISTMONK_ENABLED=true</code>
                and configure
                <code class="rounded bg-muted px-1">LISTMONK_BASE_URL</code>,
                <code class="rounded bg-muted px-1">LISTMONK_USERNAME</code>,
                and
                <code class="rounded bg-muted px-1">LISTMONK_PASSWORD</code>
                in your environment to enable list fetching.
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="px-4">Name</TableHead>
                            <TableHead class="px-4">Type</TableHead>
                            <TableHead class="px-4">Opt-in</TableHead>
                            <TableHead class="px-4">Visibility</TableHead>
                            <TableHead class="px-4">Last synced</TableHead>
                            <TableHead class="px-4 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="props.lists.length">
                            <TableRow
                                v-for="list in props.lists"
                                :key="list.id"
                            >
                                <TableCell class="px-4 py-3 font-medium">
                                    <div>{{ list.name }}</div>
                                    <p
                                        v-if="list.description"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ list.description }}
                                    </p>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <Badge variant="secondary">
                                        {{ list.type }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <Badge variant="outline">
                                        {{ list.optin }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        <Badge
                                            v-if="list.is_default_public"
                                            variant="default"
                                            >Default public</Badge
                                        >
                                        <Badge
                                            v-if="list.is_user_selectable"
                                            variant="secondary"
                                            >User selectable</Badge
                                        >
                                        <span
                                            v-if="
                                                !list.is_default_public &&
                                                !list.is_user_selectable
                                            "
                                            class="text-xs text-muted-foreground"
                                            >Hidden from users</span
                                        >
                                    </div>
                                </TableCell>
                                <TableCell
                                    class="px-4 py-3 text-xs text-muted-foreground"
                                >
                                    {{ list.last_synced_at ?? '—' }}
                                </TableCell>
                                <TableCell class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="optInTarget = list"
                                        >
                                            <UsersRound class="size-4" />
                                            Opt-in all
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    NewsletterListController.edit(
                                                        list.id,
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
                                            @click="listToDelete = list"
                                        >
                                            <Trash2
                                                class="size-4 text-destructive"
                                            />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty v-else :colspan="6">
                            No newsletter lists yet. Fetch from Listmonk to
                            mirror your existing lists, or create a new one
                            here.
                        </TableEmpty>
                    </TableBody>
                </Table>
            </div>
        </div>

        <Dialog
            :open="listToDelete !== null"
            @update:open="(o) => !o && (listToDelete = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Delete list "{{ listToDelete?.name }}"?
                    </DialogTitle>
                    <DialogDescription>
                        This removes the local mirror only. The list inside
                        Listmonk is not deleted.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="listToDelete = null">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="optInTarget !== null"
            @update:open="(o) => !o && (optInTarget = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Opt all verified users into "{{ optInTarget?.name }}"?
                    </DialogTitle>
                    <DialogDescription>
                        Every verified user will be silently subscribed via
                        Listmonk. This dispatches background jobs and may take a
                        while for large user bases.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        :disabled="optInProcessing"
                        @click="optInTarget = null"
                    >
                        Cancel
                    </Button>
                    <Button
                        :disabled="optInProcessing"
                        @click="confirmOptInAll"
                    >
                        {{ optInProcessing ? 'Queuing…' : 'Opt-in all' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
