<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Users } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create as orgaTeamCreate,
    edit as orgaTeamEdit,
    index as orgaTeamsRoute,
} from '@/routes/orga-teams';
import type { BreadcrumbItem } from '@/types';

type OrgaTeamRow = {
    id: number;
    name: string;
    slug: string;
    organizer: { id: number; username: string | null; name: string } | null;
    sub_teams_count: number;
    events_count: number;
};

defineProps<{
    orgaTeams: OrgaTeamRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: orgaTeamsRoute().url },
    { title: 'Orga-Teams', href: orgaTeamsRoute().url },
];
</script>

<template>
    <Head title="Orga-Teams" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Heading
                    title="Orga-Teams"
                    description="Reusable staff teams shown publicly per event"
                />
                <Link :href="orgaTeamCreate().url">
                    <Button>
                        <Plus class="size-4" />
                        New Orga-Team
                    </Button>
                </Link>
            </div>

            <div
                v-if="orgaTeams.length === 0"
                class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed py-16 text-center"
            >
                <Users class="size-8 text-muted-foreground" />
                <p class="text-muted-foreground">
                    No Orga-Teams yet. Create one to start showing your staff
                    publicly on event pages.
                </p>
            </div>

            <div v-else class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Organizer</TableHead>
                            <TableHead class="text-right">Sub-Teams</TableHead>
                            <TableHead class="text-right">
                                Assigned events
                            </TableHead>
                            <TableHead class="w-1" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="team in orgaTeams" :key="team.id">
                            <TableCell class="font-medium">
                                {{ team.name }}
                                <p class="text-xs text-muted-foreground">
                                    /{{ team.slug }}
                                </p>
                            </TableCell>
                            <TableCell>
                                <span v-if="team.organizer">
                                    @{{ team.organizer.username }}
                                </span>
                                <span v-else class="text-muted-foreground">
                                    —
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                {{ team.sub_teams_count }}
                            </TableCell>
                            <TableCell class="text-right">
                                {{ team.events_count }}
                            </TableCell>
                            <TableCell>
                                <Link :href="orgaTeamEdit(team.id).url">
                                    <Button variant="outline" size="sm">
                                        Manage
                                    </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
