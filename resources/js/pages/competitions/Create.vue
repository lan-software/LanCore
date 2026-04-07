<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CompetitionController from '@/actions/App/Domain/Competition/Http/Controllers/CompetitionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as competitionsRoute } from '@/routes/competitions';
import type { BreadcrumbItem } from '@/types';
import type { Game } from '@/types/domain';

const props = defineProps<{
    games: Game[];
    events: { id: number; name: string; start_date: string }[];
    selectedEventId?: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: competitionsRoute().url },
    { title: 'Competitions', href: competitionsRoute().url },
    { title: 'Create', href: CompetitionController.create().url },
];
</script>

<template>
    <Head title="Create Competition" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="competitionsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Competitions
                </Link>
            </div>

            <Form
                v-bind="CompetitionController.store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Competition Details"
                        description="Configure the basic settings for this competition"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="e.g. CS2 5v5 Tournament"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="3"
                            placeholder="Describe the competition..."
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="type">Type</Label>
                            <select
                                id="type"
                                name="type"
                                required
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="tournament">Tournament</option>
                                <option value="league">League</option>
                                <option value="race">Race</option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="stage_type">Stage Type</Label>
                            <select
                                id="stage_type"
                                name="stage_type"
                                required
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="single_elimination">
                                    Single Elimination
                                </option>
                                <option value="double_elimination">
                                    Double Elimination
                                </option>
                                <option value="round_robin">Round Robin</option>
                                <option value="swiss">Swiss</option>
                                <option value="group_stage">Group Stage</option>
                            </select>
                            <InputError :message="errors.stage_type" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="team_size">Team Size</Label>
                            <Input
                                id="team_size"
                                name="team_size"
                                type="number"
                                min="1"
                                placeholder="e.g. 5"
                            />
                            <InputError :message="errors.team_size" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="max_teams">Max Teams</Label>
                            <Input
                                id="max_teams"
                                name="max_teams"
                                type="number"
                                min="2"
                                placeholder="e.g. 16"
                            />
                            <InputError :message="errors.max_teams" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="game_id">Game</Label>
                            <select
                                id="game_id"
                                name="game_id"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">None</option>
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
                            <Label for="event_id">Event</Label>
                            <Select
                                name="event_id"
                                :default-value="
                                    props.selectedEventId
                                        ? String(props.selectedEventId)
                                        : undefined
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select an event"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="event in events"
                                        :key="event.id"
                                        :value="String(event.id)"
                                    >
                                        {{ event.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.event_id" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="starts_at">Starts At</Label>
                            <Input
                                id="starts_at"
                                name="starts_at"
                                type="datetime-local"
                            />
                            <InputError :message="errors.starts_at" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="ends_at">Ends At</Label>
                            <Input
                                id="ends_at"
                                name="ends_at"
                                type="datetime-local"
                            />
                            <InputError :message="errors.ends_at" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating...' : 'Create Competition' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
