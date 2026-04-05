<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import GameServerController from '@/actions/App/Domain/Orchestration/Http/Controllers/GameServerController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as gameServersRoute } from '@/routes/game-servers';
import type { BreadcrumbItem } from '@/types';
import type { Game, GameMode } from '@/types/domain';

defineProps<{
    games: Game[];
    gameModes: GameMode[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: gameServersRoute().url },
    { title: 'Orchestration', href: gameServersRoute().url },
    { title: 'Game Servers', href: gameServersRoute().url },
    { title: 'Create', href: GameServerController.create().url },
];
</script>

<template>
    <Head title="Add Game Server" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="gameServersRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Game Servers
                </Link>
            </div>

            <Form
                v-bind="GameServerController.store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Server Details -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Server Details"
                        description="Connection information for the game server"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="e.g. CS2 Server #1"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="host">Host</Label>
                            <Input
                                id="host"
                                name="host"
                                required
                                placeholder="192.168.1.100"
                            />
                            <InputError :message="errors.host" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="port">Port</Label>
                            <Input
                                id="port"
                                name="port"
                                type="number"
                                required
                                placeholder="27015"
                            />
                            <InputError :message="errors.port" />
                        </div>
                    </div>
                </div>

                <!-- Game Assignment -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Game Assignment"
                        description="Which game this server runs"
                    />

                    <div class="grid gap-2">
                        <Label for="game_id">Game</Label>
                        <select
                            id="game_id"
                            name="game_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">Select a game</option>
                            <option
                                v-for="game in games"
                                :key="game.id"
                                :value="game.id"
                            >
                                {{ game.name }}
                            </option>
                        </select>
                        <InputError :message="errors.game_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="game_mode_id">Game Mode (optional)</Label>
                        <select
                            id="game_mode_id"
                            name="game_mode_id"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">Any mode</option>
                            <option
                                v-for="mode in gameModes"
                                :key="mode.id"
                                :value="mode.id"
                            >
                                {{ mode.name }}
                            </option>
                        </select>
                        <InputError :message="errors.game_mode_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="allocation_type">Allocation Type</Label>
                        <select
                            id="allocation_type"
                            name="allocation_type"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">Select type</option>
                            <option value="competition">
                                Competition (reserved for matches)
                            </option>
                            <option value="casual">
                                Casual (pooled for casual play)
                            </option>
                            <option value="flexible">Flexible (both)</option>
                        </select>
                        <InputError :message="errors.allocation_type" />
                    </div>
                </div>

                <!-- Credentials -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Credentials"
                        description="Authentication details for server management"
                    />

                    <div class="grid gap-2">
                        <Label for="credentials.rcon_password"
                            >RCON Password</Label
                        >
                        <Input
                            id="credentials.rcon_password"
                            name="credentials[rcon_password]"
                            type="password"
                            placeholder="Server RCON password"
                        />
                        <InputError
                            :message="errors['credentials.rcon_password']"
                        />
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create Server' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
