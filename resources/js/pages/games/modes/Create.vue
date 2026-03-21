<script setup lang="ts">
import GameController from '@/actions/App/Domain/Games/Http/Controllers/GameController'
import GameModeController from '@/actions/App/Domain/Games/Http/Controllers/GameModeController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as gamesRoute } from '@/routes/games'
import type { BreadcrumbItem } from '@/types'
import type { Game } from '@/types/domain'
import { Form, Head, Link } from '@inertiajs/vue3'

const props = defineProps<{
    game: Game
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: gamesRoute().url },
    { title: 'Games', href: gamesRoute().url },
    { title: props.game.name, href: GameController.edit(props.game.id).url },
    { title: 'New Mode', href: GameModeController.create(props.game.id).url },
]
</script>

<template>
    <Head :title="`${game.name} – New Mode`" />

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
                v-bind="GameModeController.store.form(game.id)"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Mode Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Game Mode"
                        description="Define a new mode for this game"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
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
                            required
                            placeholder="e.g. 5"
                        />
                        <InputError :message="errors.team_size" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="parameters">Parameters (JSON)</Label>
                        <Textarea
                            id="parameters"
                            name="parameters"
                            rows="6"
                            placeholder='e.g. {"map_pool": ["dust2", "mirage", "inferno"]}'
                        />
                        <p class="text-xs text-muted-foreground">Optional JSON for extra configuration like map pools, rulesets, etc.</p>
                        <InputError :message="errors.parameters" />
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Creating…' : 'Create Mode' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
