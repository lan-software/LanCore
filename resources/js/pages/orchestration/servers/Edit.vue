<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import GameServerController from '@/actions/App/Domain/Orchestration/Http/Controllers/GameServerController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as gameServersRoute } from '@/routes/game-servers';
import type { BreadcrumbItem } from '@/types';
import type { Game, GameMode, GameServer } from '@/types/domain';

const props = defineProps<{
    server: GameServer;
    games: Game[];
    gameModes: GameMode[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: gameServersRoute().url },
    { title: 'Orchestration', href: gameServersRoute().url },
    { title: 'Game Servers', href: gameServersRoute().url },
    {
        title: props.server.name,
        href: GameServerController.edit(props.server.id).url,
    },
];

const showDeleteDialog = ref(false);

function executeDelete() {
    router.delete(GameServerController.destroy(props.server.id).url);
}

function forceRelease() {
    router.post(GameServerController.forceRelease(props.server.id).url);
}
</script>

<template>
    <Head :title="`Edit ${server.name}`" />

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

            <div class="flex items-center gap-3">
                <Heading
                    :title="server.name"
                    description="Edit game server configuration"
                />
                <Badge
                    :class="{
                        'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400':
                            server.status === 'available',
                        'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-400':
                            server.status === 'in_use',
                        'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400':
                            server.status === 'offline',
                        'bg-yellow-50 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-400':
                            server.status === 'maintenance',
                    }"
                >
                    {{ server.status.replace('_', ' ') }}
                </Badge>
            </div>

            <div
                v-if="server.active_orchestration_job"
                class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950"
            >
                <p class="text-sm font-medium text-blue-700 dark:text-blue-300">
                    Active match: Job #{{ server.active_orchestration_job.id }}
                    (Match
                    {{ server.active_orchestration_job.lanbrackets_match_id }})
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    class="mt-2"
                    @click="forceRelease"
                >
                    Force Release
                </Button>
            </div>

            <Form
                v-bind="GameServerController.update.form(server.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Server Details -->
                <div class="space-y-4">
                    <Heading variant="small" title="Server Details" />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            :default-value="server.name"
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
                                :default-value="server.host"
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
                                :default-value="String(server.port)"
                            />
                            <InputError :message="errors.port" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            name="status"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            :disabled="server.status === 'in_use'"
                        >
                            <option
                                value="available"
                                :selected="server.status === 'available'"
                            >
                                Available
                            </option>
                            <option
                                value="in_use"
                                :selected="server.status === 'in_use'"
                                disabled
                            >
                                In Use
                            </option>
                            <option
                                value="offline"
                                :selected="server.status === 'offline'"
                            >
                                Offline
                            </option>
                            <option
                                value="maintenance"
                                :selected="server.status === 'maintenance'"
                            >
                                Maintenance
                            </option>
                        </select>
                        <InputError :message="errors.status" />
                    </div>
                </div>

                <!-- Game Assignment -->
                <div class="space-y-4">
                    <Heading variant="small" title="Game Assignment" />

                    <div class="grid gap-2">
                        <Label for="game_id">Game</Label>
                        <select
                            id="game_id"
                            name="game_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option
                                v-for="game in games"
                                :key="game.id"
                                :value="game.id"
                                :selected="game.id === server.game_id"
                            >
                                {{ game.name }}
                            </option>
                        </select>
                        <InputError :message="errors.game_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="allocation_type">Allocation Type</Label>
                        <select
                            id="allocation_type"
                            name="allocation_type"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option
                                value="competition"
                                :selected="
                                    server.allocation_type === 'competition'
                                "
                            >
                                Competition
                            </option>
                            <option
                                value="casual"
                                :selected="server.allocation_type === 'casual'"
                            >
                                Casual
                            </option>
                            <option
                                value="flexible"
                                :selected="
                                    server.allocation_type === 'flexible'
                                "
                            >
                                Flexible
                            </option>
                        </select>
                        <InputError :message="errors.allocation_type" />
                    </div>
                </div>

                <!-- Credentials -->
                <div class="space-y-4">
                    <Heading variant="small" title="Credentials" />

                    <div class="grid gap-2">
                        <Label for="credentials.rcon_password"
                            >RCON Password</Label
                        >
                        <Input
                            id="credentials.rcon_password"
                            name="credentials[rcon_password]"
                            type="password"
                            placeholder="Leave blank to keep current"
                        />
                        <InputError
                            :message="errors['credentials.rcon_password']"
                        />
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                    <p v-if="recentlySuccessful" class="text-sm text-green-600">
                        Saved.
                    </p>
                </div>
            </Form>

            <!-- Danger Zone -->
            <div class="space-y-4 border-t pt-6">
                <Heading
                    variant="small"
                    title="Danger Zone"
                    description="Permanently delete this game server"
                />
                <Button
                    variant="destructive"
                    :disabled="server.status === 'in_use'"
                    @click="showDeleteDialog = true"
                >
                    Delete Server
                </Button>
            </div>
        </div>

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ server.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The server will be
                        permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false"
                        >Cancel</Button
                    >
                    <Button variant="destructive" @click="executeDelete"
                        >Delete</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
