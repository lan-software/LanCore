<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    edit as sponsorEdit,
    update,
    destroy,
} from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorController';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as sponsorsRoute } from '@/routes/sponsors';
import type { BreadcrumbItem } from '@/types';
import type { Sponsor, SponsorLevel } from '@/types/domain';

const props = defineProps<{
    sponsor: Sponsor;
    sponsorLevels: SponsorLevel[];
    events: { id: number; name: string }[];
    users: { id: number; name: string; email: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: sponsorsRoute().url },
    { title: 'Sponsors', href: sponsorsRoute().url },
    { title: props.sponsor.name, href: sponsorEdit(props.sponsor.id).url },
];

const showDeleteDialog = ref(false);
const removeLogo = ref(false);

function hasEvent(eventId: number): boolean {
    return props.sponsor.events?.some((e) => e.id === eventId) ?? false;
}

function hasManager(userId: number): boolean {
    return props.sponsor.managers?.some((m) => m.id === userId) ?? false;
}

function executeDelete() {
    router.delete(destroy(props.sponsor.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Edit ${sponsor.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="sponsorsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Sponsors
                </Link>
            </div>

            <Form
                v-bind="update.form(sponsor.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
                enctype="multipart/form-data"
            >
                <!-- Sponsor Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Sponsor Information"
                        description="Update the basic details for this sponsor"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="sponsor.name"
                            required
                            placeholder="Sponsor name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            :default-value="sponsor.description ?? ''"
                            rows="3"
                            placeholder="Short description of the sponsor…"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="link">Website Link</Label>
                        <Input
                            id="link"
                            name="link"
                            type="url"
                            :default-value="sponsor.link ?? ''"
                            placeholder="https://sponsor-website.com"
                        />
                        <InputError :message="errors.link" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="logo">Logo</Label>
                        <div
                            v-if="sponsor.logo_url && !removeLogo"
                            class="flex items-center gap-4"
                        >
                            <img
                                :src="sponsor.logo_url"
                                :alt="sponsor.name"
                                class="h-16 w-auto rounded border object-contain"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="removeLogo = true"
                            >
                                Remove Logo
                            </Button>
                        </div>
                        <template v-else>
                            <Input
                                id="logo"
                                name="logo"
                                type="file"
                                accept="image/*"
                            />
                            <input
                                v-if="removeLogo"
                                type="hidden"
                                name="remove_logo"
                                value="1"
                            />
                        </template>
                        <InputError :message="errors.logo" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="sponsor_level_id">Sponsor Level</Label>
                        <Select
                            name="sponsor_level_id"
                            :default-value="
                                sponsor.sponsor_level_id
                                    ? String(sponsor.sponsor_level_id)
                                    : undefined
                            "
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Select a level (optional)"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="level in sponsorLevels"
                                    :key="level.id"
                                    :value="String(level.id)"
                                >
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-3 rounded-full"
                                            :style="{
                                                backgroundColor: level.color,
                                            }"
                                        />
                                        {{ level.name }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.sponsor_level_id" />
                    </div>
                </div>

                <!-- Events -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Events"
                        description="Assign this sponsor to events"
                    />

                    <div v-if="events.length > 0" class="space-y-2">
                        <div
                            v-for="event in events"
                            :key="event.id"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`event-${event.id}`"
                                name="event_ids[]"
                                :value="event.id"
                                :default-value="hasEvent(event.id)"
                            />
                            <Label
                                :for="`event-${event.id}`"
                                class="cursor-pointer"
                                >{{ event.name }}</Label
                            >
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        No events available.
                    </p>
                </div>

                <!-- Managers -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Sponsor Managers"
                        description="Assign users who can manage this sponsor's page"
                    />

                    <div v-if="users.length > 0" class="space-y-2">
                        <div
                            v-for="user in users"
                            :key="user.id"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`manager-${user.id}`"
                                name="manager_ids[]"
                                :value="user.id"
                                :default-value="hasManager(user.id)"
                            />
                            <Label
                                :for="`manager-${user.id}`"
                                class="cursor-pointer"
                            >
                                {{ user.name }}
                                <span class="text-muted-foreground"
                                    >({{ user.email }})</span
                                >
                            </Label>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        No users available.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>

                    <p
                        v-if="recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >
                        Saved.
                    </p>
                </div>
            </Form>

            <!-- Delete section -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">
                            Delete Sponsor
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Permanently delete this sponsor and remove all event
                            associations.
                        </p>
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ sponsor.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The sponsor will be
                        permanently removed from all events.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="executeDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
