<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import GameController from '@/actions/App/Domain/Games/Http/Controllers/GameController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as gamesRoute } from '@/routes/games';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: gamesRoute().url },
    { title: 'Games', href: gamesRoute().url },
    { title: 'Create', href: GameController.create().url },
];
</script>

<template>
    <Head title="Create Game" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="gamesRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Games
                </Link>
            </div>

            <Form
                v-bind="GameController.store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Game Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Game Information"
                        description="Provide the basic details for this game"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="e.g. Counter-Strike 2"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="slug">Slug</Label>
                        <Input
                            id="slug"
                            name="slug"
                            required
                            placeholder="e.g. counter-strike-2"
                        />
                        <InputError :message="errors.slug" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="publisher">Publisher</Label>
                        <Input
                            id="publisher"
                            name="publisher"
                            placeholder="e.g. Valve"
                        />
                        <InputError :message="errors.publisher" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Describe the game…"
                        />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create Game' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
