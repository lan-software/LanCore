<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import ProfileEmojiPicker from '@/components/ProfileEmojiPicker.vue';
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
import {
    destroy as subTeamDestroy,
    update as subTeamUpdate,
} from '@/routes/orga-sub-teams';
import { sync as subTeamMembersSync } from '@/routes/orga-sub-teams/members';
import {
    destroy as orgaTeamDestroy,
    index as orgaTeamsRoute,
    update as orgaTeamUpdate,
} from '@/routes/orga-teams';
import { store as subTeamStore } from '@/routes/orga-teams/sub-teams';
import type { BreadcrumbItem } from '@/types';

type UserOption = { id: number; username: string | null; name: string };

type Membership = {
    id: number;
    user_id: number;
    role: 'deputy' | 'member';
    sort_order: number;
    user: UserOption | null;
};

type SubTeam = {
    id: number;
    name: string;
    description: string | null;
    emoji: string | null;
    color: string | null;
    sort_order: number;
    leader_user_id: number | null;
    leader: UserOption | null;
    memberships: Membership[];
};

type AssignedEvent = { id: number; name: string };

type OrgaTeam = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    organizer_user_id: number;
    organizer: UserOption | null;
    deputies: (UserOption & { pivot?: { sort_order: number } })[];
    sub_teams: SubTeam[];
    events: AssignedEvent[];
};

const props = defineProps<{
    orgaTeam: OrgaTeam;
    users: UserOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Orga-Teams', href: orgaTeamsRoute().url },
    { title: props.orgaTeam.name, href: '#' },
];

// --- team meta + deputies form ---
const teamForm = useForm({
    name: props.orgaTeam.name,
    slug: props.orgaTeam.slug,
    description: props.orgaTeam.description ?? '',
    organizer_user_id: props.orgaTeam.organizer_user_id,
    deputy_user_ids: props.orgaTeam.deputies.map((d) => d.id),
});

function saveTeam() {
    teamForm.patch(orgaTeamUpdate(props.orgaTeam.id).url, {
        preserveScroll: true,
    });
}

function deleteTeam() {
    if (
        !confirm(
            `Delete Orga-Team "${props.orgaTeam.name}" — this also removes all sub-teams.`,
        )
    ) {
        return;
    }

    router.delete(orgaTeamDestroy(props.orgaTeam.id).url);
}

function toggleDeputy(userId: number) {
    if (userId === teamForm.organizer_user_id) {
        return;
    }

    const idx = teamForm.deputy_user_ids.indexOf(userId);

    if (idx >= 0) {
        teamForm.deputy_user_ids.splice(idx, 1);
    } else {
        teamForm.deputy_user_ids.push(userId);
    }
}

// --- new sub-team ---
const newSubTeam = useForm({
    name: '',
    emoji: '',
    color: '',
});

function createSubTeam() {
    newSubTeam
        .transform((data) => ({
            ...data,
            emoji: data.emoji || null,
            color: data.color || null,
        }))
        .post(subTeamStore(props.orgaTeam.id).url, {
            preserveScroll: true,
            onSuccess: () => newSubTeam.reset(),
        });
}

// --- per sub-team edit ---
const expanded = ref<number | null>(null);

function toggle(subId: number) {
    expanded.value = expanded.value === subId ? null : subId;
}

function saveSubTeam(sub: SubTeam) {
    router.patch(
        subTeamUpdate(sub.id).url,
        {
            name: sub.name,
            description: sub.description,
            emoji: sub.emoji,
            color: sub.color,
            sort_order: sub.sort_order,
            leader_user_id: sub.leader_user_id,
        },
        { preserveScroll: true },
    );
}

function deleteSubTeam(sub: SubTeam) {
    if (!confirm(`Delete sub-team "${sub.name}"?`)) {
        return;
    }

    router.delete(subTeamDestroy(sub.id).url, { preserveScroll: true });
}

function syncMembers(sub: SubTeam) {
    router.patch(
        subTeamMembersSync(sub.id).url,
        {
            memberships: sub.memberships.map((m) => ({
                user_id: m.user_id,
                role: m.role,
            })),
        },
        { preserveScroll: true },
    );
}

function addMembership(
    sub: SubTeam,
    userId: number,
    role: 'deputy' | 'member',
) {
    if (sub.memberships.some((m) => m.user_id === userId)) {
        return;
    }

    sub.memberships.push({
        id: 0,
        user_id: userId,
        role,
        sort_order: sub.memberships.length,
        user: props.users.find((u) => u.id === userId) ?? null,
    });
}

function removeMembership(sub: SubTeam, userId: number) {
    const idx = sub.memberships.findIndex((m) => m.user_id === userId);

    if (idx >= 0) {
        sub.memberships.splice(idx, 1);
    }
}

const usersById = computed(() => {
    const map = new Map<number, UserOption>();

    for (const u of props.users) {
        map.set(u.id, u);
    }

    return map;
});

function userLabel(id: number): string {
    const u = usersById.value.get(id);

    if (!u) {
        return `#${id}`;
    }

    return u.username ? `@${u.username}` : u.name;
}
</script>

<template>
    <Head :title="`${orgaTeam.name} — Orga-Team`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl space-y-8 p-4">
            <div class="flex items-start justify-between gap-4">
                <Heading
                    :title="orgaTeam.name"
                    :description="`/${orgaTeam.slug}`"
                />
                <div class="flex gap-2">
                    <Link
                        v-for="evt in orgaTeam.events"
                        :key="evt.id"
                        :href="`/events/${evt.id}/orga-team`"
                        class="rounded-md border bg-card px-2 py-1 text-xs"
                    >
                        Public preview · {{ evt.name }}
                    </Link>
                </div>
            </div>

            <!-- Team meta + deputies -->
            <section class="rounded-lg border p-5">
                <h2 class="mb-4 text-lg font-semibold">Team</h2>
                <form @submit.prevent="saveTeam" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label for="t-name">Name</Label>
                            <Input id="t-name" v-model="teamForm.name" />
                        </div>
                        <div>
                            <Label for="t-slug">Slug</Label>
                            <Input id="t-slug" v-model="teamForm.slug" />
                        </div>
                    </div>
                    <div>
                        <Label for="t-desc">Description</Label>
                        <Textarea
                            id="t-desc"
                            v-model="teamForm.description"
                            rows="2"
                        />
                    </div>
                    <div>
                        <Label for="t-org">Organizer (Veranstalter)</Label>
                        <Select
                            :model-value="String(teamForm.organizer_user_id)"
                            @update:model-value="
                                (v) => (teamForm.organizer_user_id = Number(v))
                            "
                        >
                            <SelectTrigger id="t-org">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="u in users"
                                    :key="u.id"
                                    :value="String(u.id)"
                                >
                                    {{ u.name
                                    }}<span v-if="u.username">
                                        (@{{ u.username }})</span
                                    >
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label>Deputies (Stellvertreter)</Label>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <label
                                v-for="u in users"
                                :key="u.id"
                                class="flex items-center gap-2 text-sm"
                                :class="{
                                    'opacity-50':
                                        u.id === teamForm.organizer_user_id,
                                }"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        teamForm.deputy_user_ids.includes(u.id)
                                    "
                                    :disabled="
                                        u.id === teamForm.organizer_user_id
                                    "
                                    @change="toggleDeputy(u.id)"
                                />
                                <span>
                                    {{ u.name
                                    }}<span
                                        v-if="u.username"
                                        class="text-muted-foreground"
                                    >
                                        @{{ u.username }}</span
                                    >
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <Button
                            type="button"
                            variant="destructive"
                            @click="deleteTeam"
                        >
                            <Trash2 class="size-4" /> Delete team
                        </Button>
                        <Button type="submit" :disabled="teamForm.processing">
                            Save team
                        </Button>
                    </div>
                </form>
            </section>

            <!-- Sub-Teams -->
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">Sub-Teams</h2>

                <form
                    @submit.prevent="createSubTeam"
                    class="flex items-end gap-3 rounded-lg border p-4"
                >
                    <div class="flex-1">
                        <Label for="ns-name">New sub-team name</Label>
                        <Input
                            id="ns-name"
                            v-model="newSubTeam.name"
                            placeholder="e.g. Tech"
                            required
                        />
                    </div>
                    <div>
                        <Label>Emoji</Label>
                        <ProfileEmojiPicker
                            name="new_sub_team_emoji"
                            :model-value="newSubTeam.emoji || null"
                            placeholder="🛠"
                            @update:model-value="
                                (v) => (newSubTeam.emoji = v ?? '')
                            "
                        />
                    </div>
                    <div class="w-28">
                        <Label for="ns-color">Color</Label>
                        <Input
                            id="ns-color"
                            v-model="newSubTeam.color"
                            placeholder="#22d3ee"
                        />
                    </div>
                    <Button type="submit" :disabled="newSubTeam.processing">
                        <Plus class="size-4" /> Add
                    </Button>
                </form>

                <div
                    v-for="sub in orgaTeam.sub_teams"
                    :key="sub.id"
                    class="rounded-lg border bg-card"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between p-4 text-left"
                        @click="toggle(sub.id)"
                    >
                        <div class="flex items-center gap-2">
                            <span v-if="sub.emoji" class="text-xl">{{
                                sub.emoji
                            }}</span>
                            <span class="font-medium">{{ sub.name }}</span>
                            <span class="text-xs text-muted-foreground">
                                Leader:
                                {{
                                    sub.leader_user_id
                                        ? userLabel(sub.leader_user_id)
                                        : '—'
                                }}
                                · {{ sub.memberships.length }} members
                            </span>
                        </div>
                        <span class="text-xs text-muted-foreground">
                            {{ expanded === sub.id ? 'Hide' : 'Edit' }}
                        </span>
                    </button>

                    <div
                        v-if="expanded === sub.id"
                        class="space-y-4 border-t p-4"
                    >
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Name</Label>
                                <Input v-model="sub.name" />
                            </div>
                            <div>
                                <Label>Sort</Label>
                                <Input
                                    type="number"
                                    v-model.number="sub.sort_order"
                                />
                            </div>
                            <div>
                                <Label>Emoji</Label>
                                <ProfileEmojiPicker
                                    :name="`sub_team_emoji_${sub.id}`"
                                    :model-value="sub.emoji"
                                    @update:model-value="(v) => (sub.emoji = v)"
                                />
                            </div>
                            <div>
                                <Label>Color</Label>
                                <Input v-model="sub.color" />
                            </div>
                        </div>

                        <div>
                            <Label>Description</Label>
                            <Textarea v-model="sub.description" rows="2" />
                        </div>

                        <div>
                            <Label>Leader</Label>
                            <Select
                                :model-value="
                                    sub.leader_user_id === null
                                        ? ''
                                        : String(sub.leader_user_id)
                                "
                                @update:model-value="
                                    (v) =>
                                        (sub.leader_user_id = v
                                            ? Number(v)
                                            : null)
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="No leader" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="u in users"
                                        :key="u.id"
                                        :value="String(u.id)"
                                    >
                                        {{ u.name
                                        }}<span v-if="u.username">
                                            (@{{ u.username }})</span
                                        >
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="destructive"
                                @click="deleteSubTeam(sub)"
                            >
                                <Trash2 class="size-4" /> Delete sub-team
                            </Button>
                            <Button type="button" @click="saveSubTeam(sub)">
                                Save sub-team
                            </Button>
                        </div>

                        <!-- Members -->
                        <div class="mt-4 border-t pt-4">
                            <p class="mb-2 text-sm font-medium">Members</p>
                            <div
                                v-for="m in sub.memberships"
                                :key="m.user_id"
                                class="mb-2 flex items-center gap-2 text-sm"
                            >
                                <span class="flex-1">
                                    {{ userLabel(m.user_id) }}
                                </span>
                                <Select
                                    :model-value="m.role"
                                    @update:model-value="
                                        (v) =>
                                            (m.role = v as 'deputy' | 'member')
                                    "
                                >
                                    <SelectTrigger class="w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="deputy">
                                            Fallback Leader
                                        </SelectItem>
                                        <SelectItem value="member">
                                            Member
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="removeMembership(sub, m.user_id)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>

                            <div class="mt-3 flex items-end gap-2">
                                <div class="flex-1">
                                    <Label>Add user</Label>
                                    <Select
                                        :model-value="''"
                                        @update:model-value="
                                            (v) =>
                                                v &&
                                                addMembership(
                                                    sub,
                                                    Number(v),
                                                    'member',
                                                )
                                        "
                                    >
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Pick a user"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="u in users.filter(
                                                    (u) =>
                                                        u.id !==
                                                            orgaTeam.organizer_user_id &&
                                                        !sub.memberships.some(
                                                            (m) =>
                                                                m.user_id ===
                                                                u.id,
                                                        ),
                                                )"
                                                :key="u.id"
                                                :value="String(u.id)"
                                            >
                                                {{ u.name
                                                }}<span v-if="u.username">
                                                    (@{{ u.username }})</span
                                                >
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button type="button" @click="syncMembers(sub)">
                                    Save members
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
