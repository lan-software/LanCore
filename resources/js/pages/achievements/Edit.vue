<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    update,
    destroy,
} from '@/actions/App/Domain/Achievements/Http/Controllers/AchievementController';
import { edit as achievementEdit } from '@/actions/App/Domain/Achievements/Http/Controllers/AchievementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as achievementsRoute } from '@/routes/achievements';
import type { BreadcrumbItem } from '@/types';
import type { Achievement, GrantableEvent } from '@/types/domain';

const props = defineProps<{
    achievement: Achievement;
    grantableEvents: GrantableEvent[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: achievementsRoute().url },
    { title: 'Achievements', href: achievementsRoute().url },
    {
        title: 'Edit',
        href: achievementEdit({ achievement: props.achievement.id }).url,
    },
];

const form = useForm({
    name: props.achievement.name,
    description: props.achievement.description ?? '',
    notification_text: props.achievement.notification_text ?? '',
    color: props.achievement.color,
    icon: props.achievement.icon,
    is_active: props.achievement.is_active,
    event_classes: [...(props.achievement.event_classes ?? [])],
});

function toggleEvent(eventClass: string) {
    const index = form.event_classes.indexOf(eventClass);

    if (index === -1) {
        form.event_classes.push(eventClass);
    } else {
        form.event_classes.splice(index, 1);
    }
}

function submit() {
    form.patch(update({ achievement: props.achievement.id }).url, {
        preserveScroll: true,
    });
}

function deleteAchievement() {
    if (confirm('Are you sure you want to delete this achievement?')) {
        router.delete(destroy({ achievement: props.achievement.id }).url);
    }
}
</script>

<template>
    <Head title="Edit Achievement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-8 p-4">
            <div class="flex items-center justify-between">
                <Link
                    :href="achievementsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Achievements
                </Link>
                <div class="flex items-center gap-2">
                    <Badge v-if="achievement.is_active" variant="default"
                        >Active</Badge
                    >
                    <Badge v-else variant="secondary">Inactive</Badge>
                    <span
                        class="inline-block size-4 rounded-full border"
                        :style="{ backgroundColor: achievement.color }"
                    />
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Achievement Information"
                        description="Update the achievement badge details"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="Achievement name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Describe what this achievement represents"
                            rows="3"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="notification_text">Notification Text</Label>
                        <Textarea
                            id="notification_text"
                            v-model="form.notification_text"
                            placeholder="Text shown when a user earns this achievement"
                            rows="2"
                        />
                        <p class="text-xs text-muted-foreground">
                            Max 500 characters. Sent to the user when they earn
                            the badge.
                        </p>
                        <InputError :message="form.errors.notification_text" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="color">Color</Label>
                            <div class="flex items-center gap-2">
                                <input
                                    id="color"
                                    v-model="form.color"
                                    type="color"
                                    class="size-10 cursor-pointer rounded border border-input"
                                />
                                <Input
                                    v-model="form.color"
                                    class="flex-1 font-mono"
                                    placeholder="#3b82f6"
                                />
                            </div>
                            <InputError :message="form.errors.color" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="icon">Icon</Label>
                            <Input
                                id="icon"
                                v-model="form.icon"
                                placeholder="e.g. trophy, star, medal"
                            />
                            <InputError :message="form.errors.icon" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            v-model:checked="form.is_active"
                        />
                        <Label for="is_active" class="text-sm font-normal"
                            >Active</Label
                        >
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Trigger Events"
                        description="Select which events will grant this achievement to users"
                    />

                    <div class="space-y-3">
                        <div
                            v-for="event in grantableEvents"
                            :key="event.value"
                            class="flex items-center gap-2"
                        >
                            <input
                                type="checkbox"
                                :id="`event-${event.value}`"
                                :checked="
                                    form.event_classes.includes(event.value)
                                "
                                class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                                @change="toggleEvent(event.value)"
                            />
                            <Label
                                :for="`event-${event.value}`"
                                class="text-sm font-normal"
                            >
                                {{ event.label }}
                            </Label>
                        </div>
                    </div>
                    <InputError :message="form.errors.event_classes" />
                </div>

                <div class="flex items-center justify-between">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                    <Button
                        variant="destructive"
                        type="button"
                        @click="deleteAchievement"
                    >
                        Delete Achievement
                    </Button>
                </div>
            </form>

            <div
                v-if="achievement.users_count"
                class="text-sm text-muted-foreground"
            >
                This achievement has been earned by
                {{ achievement.users_count }} user(s).
            </div>
        </div>
    </AppLayout>
</template>
