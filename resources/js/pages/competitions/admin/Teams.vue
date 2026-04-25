<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Users } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AdminTeamController from '@/actions/App/Domain/Competition/Http/Controllers/AdminTeamController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as adminTeamsRoute } from '@/routes/admin/teams';
import type { BreadcrumbItem } from '@/types';

const { t } = useI18n();

interface Team {
    id: number;
    name: string;
    tag: string | null;
    active_members_count: number;
    captain: { id: number; name: string } | null;
    competition: { id: number; name: string; status: string } | null;
}

const props = defineProps<{
    teams: {
        data: Team[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
        competition_id: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('common.administration'), href: adminTeamsRoute().url },
    { title: t('competitions.admin.competition'), href: adminTeamsRoute().url },
    { title: t('competitions.admin.teamsHeading'), href: adminTeamsRoute().url },
];

const search = ref(props.filters.search);

let debounce: ReturnType<typeof setTimeout>;
watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            adminTeamsRoute().url,
            { search: val || undefined },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function statusColor(status: string): string {
    const map: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        registration_open:
            'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
        registration_closed:
            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
        running:
            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
        finished:
            'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
        archived:
            'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
    };

    return map[status] ?? '';
}
</script>

<template>
    <Head :title="$t('competitions.admin.teamsHeading')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <Heading
                    :title="$t('competitions.admin.teamsHeading')"
                    :description="$t('competitions.admin.teamsDescription')"
                />
            </div>

            <div class="flex items-center gap-3">
                <div class="relative max-w-sm flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        :placeholder="$t('competitions.admin.searchPlaceholder')"
                        class="pl-9"
                    />
                </div>
            </div>

            <div class="rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b text-left text-xs text-muted-foreground"
                        >
                            <th class="px-4 py-3 font-medium">
                                {{ $t('competitions.admin.team') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ $t('competitions.admin.competition') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ $t('competitions.captain') }}
                            </th>
                            <th class="px-4 py-3 text-center font-medium">
                                {{ $t('competitions.admin.members') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ $t('common.status') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="team in teams.data"
                            :key="team.id"
                            class="transition hover:bg-accent/50"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="
                                        AdminTeamController.edit({
                                            team: team.id,
                                        }).url
                                    "
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ team.name }}
                                </Link>
                                <span
                                    v-if="team.tag"
                                    class="ml-1 text-xs text-muted-foreground"
                                    >[{{ team.tag }}]</span
                                >
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ team.competition?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ team.captain?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1">
                                    <Users class="size-3" />
                                    {{ team.active_members_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    v-if="team.competition?.status"
                                    :class="
                                        statusColor(team.competition.status)
                                    "
                                    class="text-[10px] capitalize"
                                >
                                    {{
                                        team.competition.status.replace(
                                            /_/g,
                                            ' ',
                                        )
                                    }}
                                </Badge>
                            </td>
                        </tr>
                        <tr v-if="teams.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{ $t('competitions.admin.noTeams') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="teams.last_page > 1"
                class="flex items-center justify-between"
            >
                <span class="text-sm text-muted-foreground">
                    {{
                        $t('competitions.admin.pageInfo', {
                            current: teams.current_page,
                            total: teams.last_page,
                        })
                    }}
                </span>
                <div class="flex gap-2">
                    <Button
                        v-if="teams.prev_page_url"
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="teams.prev_page_url">{{
                            $t('common.previous')
                        }}</Link>
                    </Button>
                    <Button
                        v-if="teams.next_page_url"
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="teams.next_page_url">{{
                            $t('common.next')
                        }}</Link>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
