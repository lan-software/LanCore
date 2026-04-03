<script setup lang="ts">
import { Form, Head, Link, useForm } from '@inertiajs/vue3';
import { ExternalLink } from 'lucide-vue-next';
import CompetitionController from '@/actions/App/Domain/Competition/Http/Controllers/CompetitionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as competitionsRoute } from '@/routes/competitions';
import type { BreadcrumbItem } from '@/types';
import type { Competition, CompetitionTeam, Game } from '@/types/domain';

const props = defineProps<{
    competition: Competition;
    games: Game[];
    events: { id: number; name: string; start_date: string }[];
    lanbracketsEnabled: boolean;
    lanbracketsBaseUrl: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: competitionsRoute().url },
    { title: 'Competitions', href: competitionsRoute().url },
    { title: props.competition.name, href: CompetitionController.edit(props.competition.id).url },
];

const statusTransitions: Record<string, { label: string; target: string }> = {
    draft: { label: 'Open Registration', target: 'registration_open' },
    registration_open: {
        label: 'Close Registration',
        target: 'registration_closed',
    },
    registration_closed: { label: 'Go Live', target: 'running' },
    running: { label: 'Mark Finished', target: 'finished' },
    finished: { label: 'Archive', target: 'archived' },
};

const transition = statusTransitions[props.competition.status] ?? null;

const transitionForm = useForm({ status: transition?.target ?? '' });

function submitTransition() {
    transitionForm.patch(
        CompetitionController.update(props.competition.id).url,
    );
}

const bracketViewUrl =
    props.competition.lanbrackets_id && props.competition.lanbrackets_share_token
        ? `${props.lanbracketsBaseUrl}/overlay/competitions/${props.competition.lanbrackets_id}?token=${props.competition.lanbrackets_share_token}`
        : null;

const lanbracketsAdminUrl = props.competition.lanbrackets_id
    ? `${props.lanbracketsBaseUrl}/competitions/${props.competition.lanbrackets_id}`
    : null;
</script>

<template>
    <Head :title="`Edit: ${competition.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="competitionsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Competitions
                </Link>
            </div>

            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Main form -->
                <div class="lg:col-span-2">
                    <Form
                        v-bind="CompetitionController.update.form(competition.id)"
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                    >
                        <Heading
                            variant="small"
                            :title="competition.name"
                            description="Edit competition details"
                        />

                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="competition.name"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                name="description"
                                :default-value="competition.description ?? ''"
                                rows="3"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="processing">
                                {{ processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </Form>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status & Transitions -->
                    <div
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">Status</h3>
                        <Badge variant="outline" class="mb-3 capitalize">
                            {{ competition.status.replace(/_/g, ' ') }}
                        </Badge>
                        <div v-if="transition">
                            <Button
                                size="sm"
                                class="w-full"
                                :disabled="transitionForm.processing"
                                @click="submitTransition"
                            >
                                {{ transition.label }}
                            </Button>
                        </div>
                    </div>

                    <!-- LanBrackets Links -->
                    <div
                        v-if="lanbracketsEnabled"
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">LanBrackets</h3>
                        <div class="space-y-2">
                            <template v-if="competition.lanbrackets_id">
                                <a
                                    v-if="lanbracketsAdminUrl"
                                    :href="lanbracketsAdminUrl"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex items-center gap-1 text-sm text-primary hover:underline"
                                >
                                    <ExternalLink class="size-3" />
                                    Admin UI
                                </a>
                                <a
                                    v-if="bracketViewUrl"
                                    :href="bracketViewUrl"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex items-center gap-1 text-sm text-primary hover:underline"
                                >
                                    <ExternalLink class="size-3" />
                                    Bracket View
                                </a>
                            </template>
                            <p
                                v-else
                                class="text-xs text-muted-foreground"
                            >
                                Not synced yet. Sync happens automatically
                                after creation.
                            </p>
                        </div>
                    </div>

                    <!-- Teams -->
                    <div
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            Teams ({{ competition.teams?.length ?? 0 }})
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="team in competition.teams"
                                :key="team.id"
                                class="rounded-lg border px-3 py-2 text-sm"
                            >
                                <div class="font-medium">
                                    {{ team.name }}
                                    <span
                                        v-if="team.tag"
                                        class="text-muted-foreground"
                                    >
                                        [{{ team.tag }}]
                                    </span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Captain: {{ team.captain?.name ?? 'None' }}
                                    &middot;
                                    {{ team.active_members?.length ?? 0 }}
                                    members
                                </div>
                            </div>
                            <p
                                v-if="!competition.teams?.length"
                                class="text-xs text-muted-foreground"
                            >
                                No teams registered yet.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
