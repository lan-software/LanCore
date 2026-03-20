<script setup lang="ts">
import { index as sponsorLevelsRoute, create as sponsorLevelCreate } from '@/routes/sponsor-levels'
import { edit } from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorLevelController'
import Heading from '@/components/Heading.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import type { SponsorLevel } from '@/types/domain'
import { Head, Link, router } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'

defineProps<{
    sponsorLevels: SponsorLevel[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: sponsorLevelsRoute().url },
    { title: 'Sponsor Levels', href: sponsorLevelsRoute().url },
]
</script>

<template>
    <Head title="Sponsor Levels" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Heading title="Sponsor Levels" description="Manage sponsor level tiers" />
                <Link :href="sponsorLevelCreate().url">
                    <Button>
                        <Plus class="size-4" />
                        Add Level
                    </Button>
                </Link>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Color</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Sponsors</TableHead>
                            <TableHead>Order</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="level in sponsorLevels"
                            :key="level.id"
                            class="cursor-pointer"
                            @click="router.visit(edit(level.id).url)"
                        >
                            <TableCell>
                                <span
                                    class="inline-block size-5 rounded-full border"
                                    :style="{ backgroundColor: level.color }"
                                />
                            </TableCell>
                            <TableCell class="font-medium">{{ level.name }}</TableCell>
                            <TableCell>
                                <Badge variant="secondary">{{ level.sponsors_count ?? 0 }}</Badge>
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ level.sort_order }}</TableCell>
                        </TableRow>
                        <TableRow v-if="sponsorLevels.length === 0">
                            <TableCell :colspan="4" class="text-center text-muted-foreground py-8">
                                No sponsor levels yet.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
