<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, Crown, Mail, Shield, Users, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as myTeamsRoute } from '@/routes/my-teams';
import { show as myCompetitionShow } from '@/routes/my-competitions';
import TeamController from '@/actions/App/Domain/Competition/Http/Controllers/TeamController';
import type { BreadcrumbItem } from '@/types';

interface Member {
    id: number;
    user_id: number;
    name: string | null;
    email: string | null;
    joined_at: string | null;
    is_captain: boolean;
}

interface JoinRequest {
    id: number;
    user_name: string;
    user_email: string;
    message: string | null;
    created_at: string;
}

interface PendingInvite {
    id: number;
    email: string;
    expires_at: string;
}

interface TeamDetail {
    id: number;
    name: string;
    tag: string | null;
    captain: { id: number; name: string; email: string } | null;
    is_captain: boolean;
    competition: {
        id: number;
        name: string;
        status: string;
        type: string;
        team_size: number | null;
        game: string | null;
    } | null;
    members: Member[];
}

const props = defineProps<{
    team: TeamDetail;
    canManage: boolean;
    pendingRequests: JoinRequest[];
    pendingInvites: PendingInvite[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Teams', href: myTeamsRoute().url },
    { title: props.team.name, href: '#' },
];

const leaving = ref(false);

function leaveTeam() {
    if (!props.team.competition) return;
    const msg = props.team.is_captain
        ? 'You are the captain. If you leave, captaincy transfers to another member. If you are the last member, the team is deleted. Continue?'
        : `Leave team "${props.team.name}"?`;
    if (!window.confirm(msg)) return;

    leaving.value = true;
    router.post(
        TeamController.leave({ competition: props.team.competition.id, team: props.team.id }).url,
        {},
        { onFinish: () => (leaving.value = false) },
    );
}

function resolveRequest(requestId: number, action: 'approve' | 'deny') {
    router.post(`/teams/join-requests/${requestId}/resolve`, { action }, { preserveScroll: true });
}

const inviteForm = useForm({ email: '' });

function sendInvite() {
    if (!props.team.competition) return;
    inviteForm.post(
        TeamController.invite({ competition: props.team.competition.id, team: props.team.id }).url,
        {
            preserveScroll: true,
            onSuccess: () => inviteForm.reset(),
        },
    );
}

function statusColor(status: string): string {
    const map: Record<string, string> = {
        registration_open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
        registration_closed: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
        running: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
        finished: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
    };
    return map[status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
}
</script>

<template>
    <Head :title="team.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <Link :href="myTeamsRoute().url" class="text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="mr-1 inline size-3" /> Back to My Teams
            </Link>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Team info -->
                <div class="space-y-6 lg:col-span-2">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold">{{ team.name }}</h1>
                            <span v-if="team.tag" class="text-lg text-muted-foreground">[{{ team.tag }}]</span>
                            <Badge v-if="team.is_captain" variant="outline">
                                <Shield class="mr-1 size-3" /> Captain
                            </Badge>
                        </div>
                        <div v-if="team.competition" class="mt-2 flex items-center gap-2">
                            <Link
                                :href="myCompetitionShow({ competition: team.competition.id }).url"
                                class="text-sm font-medium text-primary hover:underline"
                            >
                                {{ team.competition.name }}
                            </Link>
                            <Badge :class="statusColor(team.competition.status)" class="text-[10px] capitalize">
                                {{ team.competition.status.replace(/_/g, ' ') }}
                            </Badge>
                            <span v-if="team.competition.game" class="text-xs text-muted-foreground">{{ team.competition.game }}</span>
                        </div>
                    </div>

                    <!-- Members list -->
                    <div class="rounded-xl border p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-sm font-semibold">Members</h2>
                            <span class="text-xs text-muted-foreground">
                                {{ team.members.length }}<template v-if="team.competition?.team_size"> / {{ team.competition.team_size }}</template>
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="member in team.members"
                                :key="member.id"
                                class="flex items-center justify-between rounded-lg border px-4 py-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex size-8 items-center justify-center rounded-full bg-muted text-xs font-bold">
                                        {{ (member.name ?? '?').charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 text-sm font-medium">
                                            {{ member.name }}
                                            <Crown v-if="member.is_captain" class="size-3 text-amber-500" />
                                        </div>
                                        <div class="text-xs text-muted-foreground">{{ member.email }}</div>
                                    </div>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    <template v-if="member.joined_at">
                                        Joined {{ new Date(member.joined_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) }}
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Join Requests (captain only) -->
                    <div v-if="canManage && pendingRequests.length > 0" class="rounded-xl border border-blue-200 bg-blue-50/50 p-5 dark:border-blue-900 dark:bg-blue-950/30">
                        <h2 class="mb-4 text-sm font-semibold">Join Requests ({{ pendingRequests.length }})</h2>
                        <div class="space-y-3">
                            <div
                                v-for="req in pendingRequests"
                                :key="req.id"
                                class="flex items-center justify-between rounded-lg border bg-background px-4 py-3"
                            >
                                <div>
                                    <div class="text-sm font-medium">{{ req.user_name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ req.user_email }}</div>
                                    <div v-if="req.message" class="mt-1 text-xs italic text-muted-foreground">"{{ req.message }}"</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button size="sm" variant="outline" class="text-green-600 hover:text-green-700" @click="resolveRequest(req.id, 'approve')">
                                        <Check class="mr-1 size-3" /> Approve
                                    </Button>
                                    <Button size="sm" variant="outline" class="text-red-600 hover:text-red-700" @click="resolveRequest(req.id, 'deny')">
                                        <X class="mr-1 size-3" /> Deny
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Actions -->
                    <div class="rounded-xl border p-4">
                        <h3 class="mb-3 text-sm font-semibold">Actions</h3>
                        <div class="space-y-2">
                            <Link
                                v-if="team.competition"
                                :href="myCompetitionShow({ competition: team.competition.id }).url"
                                class="flex w-full items-center rounded-lg border px-4 py-2.5 text-sm transition hover:bg-accent"
                            >
                                View Competition
                            </Link>
                            <Button
                                v-if="team.competition?.status === 'registration_open'"
                                variant="outline"
                                size="sm"
                                class="w-full text-destructive hover:text-destructive"
                                :disabled="leaving"
                                @click="leaveTeam"
                            >
                                {{ leaving ? 'Leaving...' : 'Leave Team' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Invite (captain only, registration open) -->
                    <div v-if="canManage && team.competition?.status === 'registration_open'" class="rounded-xl border p-4">
                        <h3 class="mb-3 text-sm font-semibold">
                            <Mail class="mr-1 inline size-3.5" /> Invite Player
                        </h3>
                        <form class="space-y-3" @submit.prevent="sendInvite">
                            <div class="grid gap-1.5">
                                <Label for="invite-email" class="text-xs">Email</Label>
                                <Input
                                    id="invite-email"
                                    v-model="inviteForm.email"
                                    type="email"
                                    required
                                    placeholder="player@example.com"
                                />
                            </div>
                            <Button type="submit" size="sm" class="w-full" :disabled="inviteForm.processing">
                                {{ inviteForm.processing ? 'Sending...' : 'Send Invite' }}
                            </Button>
                        </form>

                        <div v-if="pendingInvites.length > 0" class="mt-4 space-y-2">
                            <p class="text-xs font-medium text-muted-foreground">Pending Invites</p>
                            <div
                                v-for="inv in pendingInvites"
                                :key="inv.id"
                                class="flex items-center justify-between rounded border px-3 py-1.5 text-xs"
                            >
                                <span>{{ inv.email }}</span>
                                <span class="text-muted-foreground">
                                    expires {{ new Date(inv.expires_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Team details -->
                    <div class="rounded-xl border p-4">
                        <h3 class="mb-3 text-sm font-semibold">Details</h3>
                        <dl class="space-y-2 text-sm">
                            <div v-if="team.captain" class="flex justify-between">
                                <dt class="text-muted-foreground">Captain</dt>
                                <dd>{{ team.captain.name }}</dd>
                            </div>
                            <div v-if="team.competition?.type" class="flex justify-between">
                                <dt class="text-muted-foreground">Type</dt>
                                <dd class="capitalize">{{ team.competition.type }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Members</dt>
                                <dd>
                                    {{ team.members.length }}<template v-if="team.competition?.team_size"> / {{ team.competition.team_size }}</template>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
