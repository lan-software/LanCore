<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ThemeController from '@/actions/App/Domain/Theme/Http/Controllers/ThemeController';
import InputError from '@/components/InputError.vue';
import ThemePalettePicker from '@/components/theme/ThemePalettePicker.vue';
import ThemePreview from '@/components/theme/ThemePreview.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as themesIndex } from '@/routes/themes';
import type { BreadcrumbItem } from '@/types';
import type { PaletteVariablesSchema } from '@/types/theme-editor';

defineProps<{
    paletteVariables: PaletteVariablesSchema;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: themesIndex().url },
    { title: 'Themes', href: themesIndex().url },
    { title: 'Create', href: ThemeController.create().url },
];

const lightOverrides = ref<Record<string, string>>({});
const darkOverrides = ref<Record<string, string>>({});

const form = useForm<{
    name: string;
    description: string;
    light_config: Record<string, string> | null;
    dark_config: Record<string, string> | null;
}>({
    name: '',
    description: '',
    light_config: null,
    dark_config: null,
});

function nullIfEmpty(
    map: Record<string, string>,
): Record<string, string> | null {
    return Object.keys(map).length === 0 ? null : map;
}

function submit() {
    form.light_config = nullIfEmpty(lightOverrides.value);
    form.dark_config = nullIfEmpty(darkOverrides.value);
    form.post(ThemeController.store().url);
}
</script>

<template>
    <Head title="Create Theme" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div>
                <Link
                    :href="themesIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Back to themes
                </Link>
            </div>

            <form @submit.prevent="submit">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,420px)_1fr]">
                    <div class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                placeholder="e.g. Retro Classic"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                placeholder="Optional short description"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-3">
                            <div class="flex items-center justify-between">
                                <Label class="text-sm font-medium"
                                    >Light palette</Label
                                >
                                <span class="text-xs text-muted-foreground"
                                    >Applies on `:root`</span
                                >
                            </div>
                            <ThemePalettePicker
                                heading-id="light"
                                :schema="paletteVariables"
                                v-model="lightOverrides"
                            />
                            <InputError :message="form.errors.light_config" />
                        </div>

                        <div class="grid gap-3">
                            <div class="flex items-center justify-between">
                                <Label class="text-sm font-medium"
                                    >Dark palette</Label
                                >
                                <span class="text-xs text-muted-foreground"
                                    >Applies on `.dark`</span
                                >
                            </div>
                            <ThemePalettePicker
                                heading-id="dark"
                                :schema="paletteVariables"
                                v-model="darkOverrides"
                            />
                            <InputError :message="form.errors.dark_config" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">
                                {{
                                    form.processing
                                        ? 'Creating…'
                                        : 'Create theme'
                                }}
                            </Button>
                        </div>
                    </div>

                    <div class="lg:sticky lg:top-4 lg:self-start">
                        <ThemePreview
                            :light-config="lightOverrides"
                            :dark-config="darkOverrides"
                        />
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
