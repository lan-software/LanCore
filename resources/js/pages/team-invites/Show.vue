<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Clock, Users, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

interface InviteData {
    id: number;
    token: string;
    team: {
        name: string;
        tag: string | null;
        members_count: number;
        team_size: number | null;
    };
    competition: {
        name: string;
        game: string | null;
    } | null;
    invited_by: string;
    is_expired: boolean;
    is_pending: boolean;
    is_accepted: boolean;
    is_declined: boolean;
    expires_at: string;
}

const props = defineProps<{
    invite: InviteData;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);

const accepting = ref(false);
const declining = ref(false);

function accept() {
    accepting.value = true;
    router.post(
        `/team-invites/${props.invite.token}/accept`,
        {},
        {
            onFinish: () => (accepting.value = false),
        },
    );
}

function decline() {
    if (!window.confirm('Decline this invite?')) return;
    declining.value = true;
    router.post(
        `/team-invites/${props.invite.token}/decline`,
        {},
        {
            onFinish: () => (declining.value = false),
        },
    );
}
</script>

<template>
    <Head title="Team Invite" />

    <AppLayout>
        <div class="flex h-full flex-1 items-center justify-center p-4">
            <div class="w-full max-w-md rounded-xl border p-8 text-center">
                <!-- Already resolved -->
                <template v-if="invite.is_accepted">
                    <CheckCircle2 class="mx-auto size-12 text-green-500" />
                    <h1 class="mt-4 text-xl font-bold">Invite Accepted</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        You've already joined
                        <strong>{{ invite.team.name }}</strong
                        >.
                    </p>
                </template>

                <template v-else-if="invite.is_declined">
                    <XCircle class="mx-auto size-12 text-muted-foreground" />
                    <h1 class="mt-4 text-xl font-bold">Invite Declined</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        This invite has been declined.
                    </p>
                </template>

                <template v-else-if="invite.is_expired">
                    <Clock class="mx-auto size-12 text-muted-foreground" />
                    <h1 class="mt-4 text-xl font-bold">Invite Expired</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        This invite expired on
                        {{ new Date(invite.expires_at).toLocaleDateString() }}.
                        Ask the team captain to send a new one.
                    </p>
                </template>

                <!-- Pending invite -->
                <template v-else-if="invite.is_pending">
                    <Users class="mx-auto size-12 text-primary" />
                    <h1 class="mt-4 text-xl font-bold">You're invited!</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        <strong>{{ invite.invited_by }}</strong> invited you to
                        join
                    </p>

                    <div class="mt-4 rounded-lg border p-4 text-left">
                        <div class="font-semibold">
                            {{ invite.team.name }}
                            <span
                                v-if="invite.team.tag"
                                class="text-muted-foreground"
                                >[{{ invite.team.tag }}]</span
                            >
                        </div>
                        <div
                            v-if="invite.competition"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            {{ invite.competition.name }}
                            <span v-if="invite.competition.game">
                                &middot; {{ invite.competition.game }}</span
                            >
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ invite.team.members_count
                            }}<template v-if="invite.team.team_size">
                                / {{ invite.team.team_size }}</template
                            >
                            members
                        </div>
                    </div>

                    <div class="mt-2 text-xs text-muted-foreground">
                        Expires
                        {{
                            new Date(invite.expires_at).toLocaleDateString(
                                undefined,
                                {
                                    month: 'long',
                                    day: 'numeric',
                                    year: 'numeric',
                                },
                            )
                        }}
                    </div>

                    <template v-if="user">
                        <div
                            class="mt-6 flex items-center justify-center gap-3"
                        >
                            <Button :disabled="accepting" @click="accept">
                                {{ accepting ? 'Joining...' : 'Accept & Join' }}
                            </Button>
                            <Button
                                variant="outline"
                                :disabled="declining"
                                @click="decline"
                            >
                                {{ declining ? 'Declining...' : 'Decline' }}
                            </Button>
                        </div>
                    </template>
                    <template v-else>
                        <p class="mt-6 text-sm text-muted-foreground">
                            You need to
                            <a
                                href="/login"
                                class="font-medium text-primary hover:underline"
                                >log in</a
                            >
                            to accept this invite.
                        </p>
                    </template>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
