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

type Theme = {
    id: number;
    name: string;
    description: string | null;
    light_config: Record<string, string> | null;
    dark_config: Record<string, string> | null;
};

const props = defineProps<{
    theme: Theme;
    paletteVariables: PaletteVariablesSchema;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: themesIndex().url },
    { title: 'Themes', href: themesIndex().url },
    { title: props.theme.name, href: ThemeController.edit(props.theme.id).url },
];

const lightOverrides = ref<Record<string, string>>({
    ...(props.theme.light_config ?? {}),
});
const darkOverrides = ref<Record<string, string>>({
    ...(props.theme.dark_config ?? {}),
});

const form = useForm<{
    name: string;
    description: string;
    light_config: Record<string, string> | null;
    dark_config: Record<string, string> | null;
}>({
    name: props.theme.name,
    description: props.theme.description ?? '',
    light_config: props.theme.light_config,
    dark_config: props.theme.dark_config,
});

function nullIfEmpty(
    map: Record<string, string>,
): Record<string, string> | null {
    return Object.keys(map).length === 0 ? null : map;
}

function submit() {
    form.light_config = nullIfEmpty(lightOverrides.value);
    form.dark_config = nullIfEmpty(darkOverrides.value);
    form.patch(ThemeController.update(props.theme.id).url);
}
</script>

<template>
    <Head :title="`Edit ${theme.name}`" />

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
                                    form.processing ? 'Saving…' : 'Save changes'
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
