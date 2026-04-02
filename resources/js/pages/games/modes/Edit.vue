<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3'
import { Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import GameController from '@/actions/App/Domain/Games/Http/Controllers/GameController'
import GameModeController from '@/actions/App/Domain/Games/Http/Controllers/GameModeController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as gamesRoute } from '@/routes/games'
import type { BreadcrumbItem } from '@/types'
import type { Game, GameMode } from '@/types/domain'

const props = defineProps<{
    game: Game
    gameMode: GameMode
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: gamesRoute().url },
    { title: 'Games', href: gamesRoute().url },
    { title: props.game.name, href: GameController.edit(props.game.id).url },
    { title: props.gameMode.name, href: GameModeController.edit({ game: props.game.id, mode: props.gameMode.id }).url },
]

const showDeleteDialog = ref(false)

function executeDelete() {
    router.delete(GameModeController.destroy({ game: props.game.id, mode: props.gameMode.id }).url, {
        onSuccess: () => {
            showDeleteDialog.value = false
        },
    })
}

const parametersJson = props.gameMode.parameters ? JSON.stringify(props.gameMode.parameters, null, 2) : ''
</script>

<template>
    <Head :title="`${game.name} – Edit ${gameMode.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <!-- Back link -->
            <div>
                <Link
                    :href="GameController.edit(game.id).url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to {{ game.name }}
                </Link>
            </div>

            <Form
                v-bind="GameModeController.update.form({ game: game.id, mode: gameMode.id })"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Mode Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Game Mode"
                        description="Update the details for this mode"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="gameMode.name"
                            required
                            placeholder="e.g. 5v5 Competitive"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="slug">Slug</Label>
                        <Input
                            id="slug"
                            name="slug"
                            :default-value="gameMode.slug"
                            required
                            placeholder="e.g. 5v5-competitive"
                        />
                        <InputError :message="errors.slug" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            :default-value="gameMode.description ?? ''"
                            rows="3"
                            placeholder="Describe this mode…"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="team_size">Team Size</Label>
                        <Input
                            id="team_size"
                            name="team_size"
                            type="number"
                            min="1"
                            :default-value="String(gameMode.team_size)"
                            required
                        />
                        <InputError :message="errors.team_size" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="parameters">Parameters (JSON)</Label>
                        <Textarea
                            id="parameters"
                            name="parameters"
                            :default-value="parametersJson"
                            rows="6"
                            placeholder='e.g. {"map_pool": ["dust2", "mirage", "inferno"]}'
                        />
                        <p class="text-xs text-muted-foreground">Optional JSON for extra configuration like map pools, rulesets, etc.</p>
                        <InputError :message="errors.parameters" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            :default-checked="gameMode.is_active"
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

            <!-- Delete section -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">Delete Mode</h3>
                        <p class="text-sm text-muted-foreground">Permanently delete this game mode.</p>
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
                    <DialogTitle>Delete {{ gameMode.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. This game mode will be permanently removed.
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
