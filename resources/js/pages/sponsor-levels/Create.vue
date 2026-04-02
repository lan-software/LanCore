<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3'
import { store } from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorLevelController'
import { create as sponsorLevelCreate } from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorLevelController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as sponsorLevelsRoute } from '@/routes/sponsor-levels'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: sponsorLevelsRoute().url },
    { title: 'Sponsor Levels', href: sponsorLevelsRoute().url },
    { title: 'Create', href: sponsorLevelCreate().url },
]
</script>

<template>
    <Head title="Create Sponsor Level" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <div>
                <Link
                    :href="sponsorLevelsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Sponsor Levels
                </Link>
            </div>

            <Form
                v-bind="store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Sponsor Level"
                        description="Define a new sponsor tier"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="e.g. Gold, Silver, Bronze"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="color">Color</Label>
                        <div class="flex items-center gap-3">
                            <Input
                                id="color"
                                name="color"
                                type="color"
                                class="h-10 w-16 p-1"
                                value="#000000"
                            />
                            <span class="text-sm text-muted-foreground">Choose a hex color for this level</span>
                        </div>
                        <InputError :message="errors.color" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create Level' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
