<script setup lang="ts">
import GameController from '@/actions/App/Domain/Games/Http/Controllers/GameController'
import GameModeController from '@/actions/App/Domain/Games/Http/Controllers/GameModeController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as gamesRoute } from '@/routes/games'
import type { BreadcrumbItem } from '@/types'
import type { Game } from '@/types/domain'
import { Form, Head, Link, router } from '@inertiajs/vue3'
import { Check, Pencil, Plus, Trash2, X } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps<{
    game: Game
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: gamesRoute().url },
    { title: 'Games', href: gamesRoute().url },
    { title: props.game.name, href: GameController.edit(props.game.id).url },
]

const showDeleteDialog = ref(false)

function executeDelete() {
    router.delete(GameController.destroy(props.game.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false
        },
    })
}
</script>

<template>
    <Head :title="`Edit ${game.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <!-- Back link -->
            <div>
                <Link
                    :href="gamesRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Games
                </Link>
            </div>

            <Form
                v-bind="GameController.update.form(game.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Game Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Game Information"
                        description="Update the basic details for this game"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="game.name"
                            required
                            placeholder="e.g. Counter-Strike 2"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="slug">Slug</Label>
                        <Input
                            id="slug"
                            name="slug"
                            :default-value="game.slug"
                            required
                            placeholder="e.g. counter-strike-2"
                        />
                        <InputError :message="errors.slug" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="publisher">Publisher</Label>
                        <Input
                            id="publisher"
                            name="publisher"
                            :default-value="game.publisher ?? ''"
                            placeholder="e.g. Valve"
                        />
                        <InputError :message="errors.publisher" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            :default-value="game.description ?? ''"
                            rows="4"
                            placeholder="Describe the game…"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            :default-checked="game.is_active"
                            :value="true"
                        />
                        <Label for="is_active">Active</Label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>

                    <p
                        v-if="recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >
                        Saved.
                    </p>
                </div>
            </Form>

            <!-- Game Modes Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Game Modes"
                        description="Manage the available modes for this game"
                    />
                    <Button as-child size="sm">
                        <Link :href="GameModeController.create(game.id).url">
                            <Plus class="size-4" />
                            Add Mode
                        </Link>
                    </Button>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-4">Name</TableHead>
                                <TableHead class="px-4">Team Size</TableHead>
                                <TableHead class="px-4">Active</TableHead>
                                <TableHead class="px-4 w-24">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="mode in game.game_modes"
                                :key="mode.id"
                            >
                                <TableCell class="px-4 py-3 font-medium">{{ mode.name }}</TableCell>
                                <TableCell class="px-4 py-3">{{ mode.team_size }}v{{ mode.team_size }}</TableCell>
                                <TableCell class="px-4 py-3">
                                    <Check v-if="mode.is_active" class="size-4 text-green-600" />
                                    <X v-else class="size-4 text-muted-foreground" />
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <div class="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            as-child
                                        >
                                            <Link :href="GameModeController.edit(game.id, mode.id).url">
                                                <Pencil class="size-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!game.game_modes?.length">
                                <TableCell :colspan="4" class="px-4 py-6 text-center text-muted-foreground">
                                    No game modes yet.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Delete section -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">Delete Game</h3>
                        <p class="text-sm text-muted-foreground">Permanently delete this game and all its modes.</p>
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ game.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The game and all its modes will be permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showDeleteDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="executeDelete"
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
