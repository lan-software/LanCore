<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();

const props = defineProps<{
    games: Game[];
    events: { id: number; name: string; start_date: string }[];
    selectedEventId?: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('common.administration'), href: competitionsRoute().url },
    { title: t('navigation.competitions'), href: competitionsRoute().url },
    { title: t('common.create'), href: CompetitionController.create().url },
];
</script>

<template>
    <Head :title="$t('competitions.createTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="competitionsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ $t('competitions.backToList') }}
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
                        :title="$t('competitions.form.detailsHeading')"
                        :description="$t('competitions.form.detailsDescription')"
                    />

                    <div class="grid gap-2">
                        <Label for="name">{{ $t('common.name') }}</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            :placeholder="$t('competitions.form.namePlaceholder')"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">{{
                            $t('common.description')
                        }}</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="3"
                            :placeholder="
                                $t('competitions.form.descriptionPlaceholder')
                            "
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="type">{{
                                $t('competitions.form.type')
                            }}</Label>
                            <select
                                id="type"
                                name="type"
                                required
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="tournament">
                                    {{ $t('competitions.types.tournament') }}
                                </option>
                                <option value="league">
                                    {{ $t('competitions.types.league') }}
                                </option>
                                <option value="race">
                                    {{ $t('competitions.types.race') }}
                                </option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="stage_type">{{
                                $t('competitions.form.stageType')
                            }}</Label>
                            <select
                                id="stage_type"
                                name="stage_type"
                                required
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="single_elimination">
                                    {{
                                        $t(
                                            'competitions.stageTypes.singleElimination',
                                        )
                                    }}
                                </option>
                                <option value="double_elimination">
                                    {{
                                        $t(
                                            'competitions.stageTypes.doubleElimination',
                                        )
                                    }}
                                </option>
                                <option value="round_robin">
                                    {{
                                        $t(
                                            'competitions.stageTypes.roundRobin',
                                        )
                                    }}
                                </option>
                                <option value="swiss">
                                    {{ $t('competitions.stageTypes.swiss') }}
                                </option>
                                <option value="group_stage">
                                    {{
                                        $t(
                                            'competitions.stageTypes.groupStage',
                                        )
                                    }}
                                </option>
                            </select>
                            <InputError :message="errors.stage_type" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="team_size">{{
                                $t('competitions.form.teamSize')
                            }}</Label>
                            <Input
                                id="team_size"
                                name="team_size"
                                type="number"
                                min="1"
                                :placeholder="
                                    $t('competitions.form.teamSizePlaceholder')
                                "
                            />
                            <InputError :message="errors.team_size" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="max_teams">{{
                                $t('competitions.form.maxTeams')
                            }}</Label>
                            <Input
                                id="max_teams"
                                name="max_teams"
                                type="number"
                                min="2"
                                :placeholder="
                                    $t('competitions.form.maxTeamsPlaceholder')
                                "
                            />
                            <InputError :message="errors.max_teams" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="game_id">{{
                                $t('competitions.form.game')
                            }}</Label>
                            <select
                                id="game_id"
                                name="game_id"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">
                                    {{ $t('common.none') }}
                                </option>
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
                            <Label for="event_id">{{
                                $t('competitions.form.event')
                            }}</Label>
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
                                        :placeholder="
                                            $t('competitions.form.selectEvent')
                                        "
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
                            <Label for="starts_at">{{
                                $t('competitions.form.startsAt')
                            }}</Label>
                            <Input
                                id="starts_at"
                                name="starts_at"
                                type="datetime-local"
                            />
                            <InputError :message="errors.starts_at" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="ends_at">{{
                                $t('competitions.form.endsAt')
                            }}</Label>
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
                        {{
                            processing
                                ? $t('competitions.creating')
                                : $t('competitions.submitCreate')
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
