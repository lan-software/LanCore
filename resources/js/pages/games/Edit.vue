<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Check, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import GameController from '@/actions/App/Domain/Games/Http/Controllers/GameController';
import GameModeController from '@/actions/App/Domain/Games/Http/Controllers/GameModeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as gamesRoute } from '@/routes/games';
import type { BreadcrumbItem } from '@/types';
import type { Game } from '@/types/domain';

const { t } = useI18n();

const props = defineProps<{
    game: Game;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('common.administration'), href: gamesRoute().url },
    { title: t('navigation.games'), href: gamesRoute().url },
    { title: props.game.name, href: GameController.edit(props.game.id).url },
];

const showDeleteDialog = ref(false);

function executeDelete() {
    router.delete(GameController.destroy(props.game.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}
</script>

<template>
    <Head :title="$t('games.editTitle', { name: game.name })" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="gamesRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ $t('games.backToList') }}
                </Link>
            </div>

            <Form
                v-bind="GameController.update.form(game.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Game Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        :title="$t('games.gameInfoHeading')"
                        :description="$t('games.gameInfoDescription')"
                    />

                    <div class="grid gap-2">
                        <Label for="name">{{ $t('common.name') }}</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="game.name"
                            required
                            :placeholder="$t('games.namePlaceholder')"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="slug">{{ $t('games.slug') }}</Label>
                        <Input
                            id="slug"
                            name="slug"
                            :default-value="game.slug"
                            required
                            :placeholder="$t('games.slugPlaceholder')"
                        />
                        <InputError :message="errors.slug" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="publisher">{{
                            $t('games.publisher')
                        }}</Label>
                        <Input
                            id="publisher"
                            name="publisher"
                            :default-value="game.publisher ?? ''"
                            :placeholder="$t('games.publisherPlaceholder')"
                        />
                        <InputError :message="errors.publisher" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">{{
                            $t('common.description')
                        }}</Label>
                        <Textarea
                            id="description"
                            name="description"
                            :default-value="game.description ?? ''"
                            rows="4"
                            :placeholder="$t('games.descriptionPlaceholder')"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="match_length_minutes">{{
                            $t('games.matchLengthMinutes')
                        }}</Label>
                        <Input
                            id="match_length_minutes"
                            name="match_length_minutes"
                            type="number"
                            min="1"
                            max="1440"
                            :default-value="game.match_length_minutes ?? ''"
                            :placeholder="$t('games.matchLengthMinutesPlaceholder')"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ $t('games.matchLengthMinutesHelp') }}
                        </p>
                        <InputError :message="errors.match_length_minutes" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="logo">{{ $t('games.logo') }}</Label>
                        <img
                            v-if="game.logo_url"
                            :src="game.logo_url"
                            :alt="$t('games.currentLogo')"
                            class="h-16 w-16 rounded border object-contain"
                        />
                        <Input id="logo" name="logo" type="file" accept="image/*" />
                        <p class="text-xs text-muted-foreground">{{ $t('games.logoHelp') }}</p>
                        <div v-if="game.logo_path" class="flex items-center gap-2">
                            <Checkbox id="remove_logo" name="remove_logo" :value="true" />
                            <Label for="remove_logo">{{ $t('games.removeLogo') }}</Label>
                        </div>
                        <InputError :message="errors.logo" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="banner">{{ $t('games.banner') }}</Label>
                        <img
                            v-if="game.banner_url"
                            :src="game.banner_url"
                            :alt="$t('games.currentBanner')"
                            class="h-24 w-full rounded border object-cover"
                        />
                        <Input id="banner" name="banner" type="file" accept="image/*" />
                        <p class="text-xs text-muted-foreground">{{ $t('games.bannerHelp') }}</p>
                        <div v-if="game.banner_path" class="flex items-center gap-2">
                            <Checkbox id="remove_banner" name="remove_banner" :value="true" />
                            <Label for="remove_banner">{{ $t('games.removeBanner') }}</Label>
                        </div>
                        <InputError :message="errors.banner" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            :default-checked="game.is_active"
                            :value="true"
                        />
                        <Label for="is_active">{{ $t('common.active') }}</Label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? $t('common.saving')
                                : $t('common.saveChanges')
                        }}
                    </Button>

                    <p
                        v-if="recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >
                        {{ $t('common.saved') }}
                    </p>
                </div>
            </Form>

            <!-- Game Modes Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        :title="$t('games.modesHeading')"
                        :description="$t('games.modesDescription')"
                    />
                    <Button as-child size="sm">
                        <Link :href="GameModeController.create(game.id).url">
                            <Plus class="size-4" />
                            {{ $t('games.addMode') }}
                        </Link>
                    </Button>
                </div>

                <div
                    class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="px-4">{{
                                    $t('common.name')
                                }}</TableHead>
                                <TableHead class="px-4">{{
                                    $t('games.teamSize')
                                }}</TableHead>
                                <TableHead class="px-4">{{
                                    $t('common.active')
                                }}</TableHead>
                                <TableHead class="w-24 px-4">{{
                                    $t('common.actions')
                                }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="mode in game.game_modes"
                                :key="mode.id"
                            >
                                <TableCell class="px-4 py-3 font-medium">{{
                                    mode.name
                                }}</TableCell>
                                <TableCell class="px-4 py-3"
                                    >{{ mode.team_size }}v{{
                                        mode.team_size
                                    }}</TableCell
                                >
                                <TableCell class="px-4 py-3">
                                    <Check
                                        v-if="mode.is_active"
                                        class="size-4 text-green-600"
                                    />
                                    <X
                                        v-else
                                        class="size-4 text-muted-foreground"
                                    />
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <div class="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    GameModeController.edit({
                                                        game: game.id,
                                                        mode: mode.id,
                                                    }).url
                                                "
                                            >
                                                <Pencil class="size-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!game.game_modes?.length">
                                <TableCell
                                    :colspan="4"
                                    class="px-4 py-6 text-center text-muted-foreground"
                                >
                                    {{ $t('games.noModes') }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Delete section -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">
                            {{ $t('games.deleteHeading') }}
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            {{ $t('games.deleteHint') }}
                        </p>
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="size-4" />
                        {{ $t('common.delete') }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        $t('games.deleteConfirmTitle', { name: game.name })
                    }}</DialogTitle>
                    <DialogDescription>
                        {{ $t('games.deleteConfirmDescription') }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button variant="destructive" @click="executeDelete">
                        {{ $t('common.delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
