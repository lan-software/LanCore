<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { store } from '@/actions/App/Domain/Achievements/Http/Controllers/AchievementController';
import { create as achievementCreate } from '@/actions/App/Domain/Achievements/Http/Controllers/AchievementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as achievementsRoute } from '@/routes/achievements';
import type { BreadcrumbItem } from '@/types';
import type { GrantableEvent } from '@/types/domain';

const { t } = useI18n();

defineProps<{
    grantableEvents: GrantableEvent[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('common.administration'), href: achievementsRoute().url },
    { title: t('navigation.achievements'), href: achievementsRoute().url },
    { title: t('common.create'), href: achievementCreate().url },
];

const form = useForm({
    name: '',
    description: '',
    notification_text: '',
    color: '#3b82f6',
    icon: 'trophy',
    is_active: true,
    event_classes: [] as string[],
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
    form.post(store().url);
}
</script>

<template>
    <Head :title="$t('achievements.createTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-8 p-4">
            <div>
                <Link
                    :href="achievementsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ $t('achievements.backToList') }}
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        :title="$t('achievements.form.headingInfo')"
                        :description="
                            $t('achievements.form.headingInfoDescriptionCreate')
                        "
                    />

                    <div class="grid gap-2">
                        <Label for="name">{{ $t('common.name') }}</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            :placeholder="
                                $t('achievements.form.namePlaceholder')
                            "
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">{{
                            $t('common.description')
                        }}</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            :placeholder="
                                $t('achievements.form.descriptionPlaceholder')
                            "
                            rows="3"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="notification_text">{{
                            $t('achievements.notificationText')
                        }}</Label>
                        <Textarea
                            id="notification_text"
                            v-model="form.notification_text"
                            :placeholder="
                                $t('achievements.form.notificationPlaceholder')
                            "
                            rows="2"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ $t('achievements.form.notificationHint') }}
                        </p>
                        <InputError :message="form.errors.notification_text" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="color">{{ $t('common.color') }}</Label>
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
                            <Label for="icon">{{ $t('common.icon') }}</Label>
                            <Input
                                id="icon"
                                v-model="form.icon"
                                :placeholder="
                                    $t('achievements.form.iconPlaceholder')
                                "
                            />
                            <InputError :message="form.errors.icon" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            v-model:checked="form.is_active"
                        />
                        <Label for="is_active" class="text-sm font-normal">{{
                            $t('common.active')
                        }}</Label>
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading
                        variant="small"
                        :title="$t('achievements.form.triggerHeading')"
                        :description="
                            $t('achievements.form.triggerDescription')
                        "
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

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{
                            form.processing
                                ? $t('common.creating')
                                : $t('achievements.submitCreate')
                        }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
