<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, Crown, Trash2, X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import AdminTeamController from '@/actions/App/Domain/Competition/Http/Controllers/AdminTeamController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as adminTeamsRoute } from '@/routes/admin/teams';
import type { BreadcrumbItem } from '@/types';

const { t } = useI18n();

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

interface TeamData {
    id: number;
    name: string;
    tag: string | null;
    captain_user_id: number | null;
    captain: { id: number; name: string; email: string } | null;
    competition: {
        id: number;
        name: string;
        status: string;
        type: string;
        team_size: number | null;
        game: string | null;
    } | null;
    members: Member[];
    pending_requests: JoinRequest[];
    pending_invites: PendingInvite[];
}

const props = defineProps<{
    team: TeamData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('common.administration'), href: adminTeamsRoute().url },
    {
        title: t('competitions.admin.teamsHeading'),
        href: adminTeamsRoute().url,
    },
    {
        title: props.team.name,
        href: AdminTeamController.edit({ team: props.team.id }).url,
    },
];

function removeMember(member: Member) {
    if (
        !window.confirm(
            t('competitions.admin.removeMemberConfirm', {
                name: member.name ?? '',
            }),
        )
    ) {
        return;
    }

    router.delete(`/admin/teams/${props.team.id}/members/${member.id}`, {
        preserveScroll: true,
    });
}

function resolveRequest(requestId: number, action: 'approve' | 'deny') {
    router.post(
        `/teams/join-requests/${requestId}/resolve`,
        { action },
        { preserveScroll: true },
    );
}

function deleteTeam() {
    if (
        !window.confirm(
            t('competitions.admin.deleteTeamConfirm', {
                name: props.team.name,
            }),
        )
    ) {
        return;
    }

    if (!props.team.competition) {
        return;
    }

    router.delete(
        `/competitions/${props.team.competition.id}/teams/${props.team.id}`,
    );
}

function statusColor(status: string): string {
    const map: Record<string, string> = {
        registration_open:
            'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
        registration_closed:
            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
        running:
            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
        finished:
            'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
    };

    return (
        map[status] ??
        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
    );
}
</script>

<template>
    <Head
        :title="$t('competitions.admin.editTeamTitle', { name: team.name })"
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <Link
                    :href="adminTeamsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="mr-1 inline size-3" />
                    {{ $t('competitions.admin.backToTeams') }}
                </Link>
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    @click="deleteTeam"
                >
                    <Trash2 class="mr-1 size-3" />
                    {{ $t('competitions.admin.deleteTeamButton') }}
                </Button>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <!-- Team details form -->
                    <Form
                        v-bind="
                            AdminTeamController.update.form({ team: team.id })
                        "
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                    >
                        <Heading
                            variant="small"
                            :title="team.name"
                            :description="
                                $t('competitions.admin.editTeamDescription')
                            "
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="name">{{
                                    $t('common.name')
                                }}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    :default-value="team.name"
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="tag">{{
                                    $t('competitions.admin.tag')
                                }}</Label>
                                <Input
                                    id="tag"
                                    name="tag"
                                    :default-value="team.tag ?? ''"
                                    maxlength="10"
                                />
                                <InputError :message="errors.tag" />
                            </div>
                        </div>

                        <Button type="submit" size="sm" :disabled="processing">
                            {{
                                processing
                                    ? $t('common.saving')
                                    : $t('common.saveChanges')
                            }}
                        </Button>
                    </Form>

                    <!-- Members -->
                    <div class="rounded-xl border p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-sm font-semibold">
                                {{ $t('competitions.admin.members') }}
                            </h2>
                            <span class="text-xs text-muted-foreground">
                                {{ team.members.length
                                }}<template v-if="team.competition?.team_size">
                                    / {{ team.competition.team_size }}</template
                                >
                            </span>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="member in team.members"
                                :key="member.id"
                                class="flex items-center justify-between rounded-lg border px-4 py-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-8 items-center justify-center rounded-full bg-muted text-xs font-bold"
                                    >
                                        {{
                                            (member.name ?? '?')
                                                .charAt(0)
                                                .toUpperCase()
                                        }}
                                    </div>
                                    <div>
                                        <div
                                            class="flex items-center gap-2 text-sm font-medium"
                                        >
                                            {{ member.name }}
                                            <Crown
                                                v-if="member.is_captain"
                                                class="size-3 text-amber-500"
                                            />
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ member.email }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="member.joined_at"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{
                                            new Date(
                                                member.joined_at,
                                            ).toLocaleDateString(undefined, {
                                                month: 'short',
                                                day: 'numeric',
                                            })
                                        }}
                                    </span>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="removeMember(member)"
                                    >
                                        <Trash2 class="size-3" />
                                    </Button>
                                </div>
                            </div>
                            <p
                                v-if="team.members.length === 0"
                                class="text-xs text-muted-foreground"
                            >
                                {{ $t('competitions.admin.noMembers') }}
                            </p>
                        </div>
                    </div>

                    <!-- Pending Join Requests -->
                    <div
                        v-if="team.pending_requests.length > 0"
                        class="rounded-xl border border-blue-200 bg-blue-50/50 p-5 dark:border-blue-900 dark:bg-blue-950/30"
                    >
                        <h2 class="mb-4 text-sm font-semibold">
                            {{
                                $t('competitions.admin.joinRequestsCount', {
                                    count: team.pending_requests.length,
                                })
                            }}
                        </h2>
                        <div class="space-y-3">
                            <div
                                v-for="req in team.pending_requests"
                                :key="req.id"
                                class="flex items-center justify-between rounded-lg border bg-background px-4 py-3"
                            >
                                <div>
                                    <div class="text-sm font-medium">
                                        {{ req.user_name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ req.user_email }}
                                    </div>
                                    <div
                                        v-if="req.message"
                                        class="mt-1 text-xs text-muted-foreground italic"
                                    >
                                        "{{ req.message }}"
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="text-green-600"
                                        @click="
                                            resolveRequest(req.id, 'approve')
                                        "
                                    >
                                        <Check class="mr-1 size-3" />
                                        {{ $t('competitions.admin.approve') }}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="text-red-600"
                                        @click="resolveRequest(req.id, 'deny')"
                                    >
                                        <X class="mr-1 size-3" />
                                        {{ $t('competitions.admin.deny') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <div class="rounded-xl border p-4">
                        <h3 class="mb-3 text-sm font-semibold">
                            {{ $t('competitions.admin.competition') }}
                        </h3>
                        <div v-if="team.competition" class="space-y-2 text-sm">
                            <div class="font-medium">
                                {{ team.competition.name }}
                            </div>
                            <Badge
                                :class="statusColor(team.competition.status)"
                                class="text-[10px] capitalize"
                            >
                                {{ team.competition.status.replace(/_/g, ' ') }}
                            </Badge>
                            <div
                                v-if="team.competition.game"
                                class="text-xs text-muted-foreground"
                            >
                                {{ team.competition.game }}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border p-4">
                        <h3 class="mb-3 text-sm font-semibold">
                            {{ $t('competitions.admin.details') }}
                        </h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">
                                    {{ $t('competitions.captain') }}
                                </dt>
                                <dd>{{ team.captain?.name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">
                                    {{ $t('competitions.admin.members') }}
                                </dt>
                                <dd>{{ team.members.length }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">
                                    {{
                                        $t('competitions.admin.pendingRequests')
                                    }}
                                </dt>
                                <dd>{{ team.pending_requests.length }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">
                                    {{
                                        $t('competitions.admin.pendingInvites')
                                    }}
                                </dt>
                                <dd>{{ team.pending_invites.length }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div
                        v-if="team.pending_invites.length > 0"
                        class="rounded-xl border p-4"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            {{ $t('competitions.admin.pendingInvites') }}
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="inv in team.pending_invites"
                                :key="inv.id"
                                class="flex items-center justify-between text-xs"
                            >
                                <span>{{ inv.email }}</span>
                                <span class="text-muted-foreground">
                                    {{
                                        new Date(
                                            inv.expires_at,
                                        ).toLocaleDateString(undefined, {
                                            month: 'short',
                                            day: 'numeric',
                                        })
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
