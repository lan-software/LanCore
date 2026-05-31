<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ImagePlus, Trash2, X } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import EventController from '@/actions/App/Domain/Event/Http/Controllers/EventController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { index as eventsRoute } from '@/routes/events';
import type { BreadcrumbItem } from '@/types';
import type { Event } from '@/types/domain';

const { t } = useI18n();

const props = defineProps<{
    event: Event;
    venues: { id: string; name: string }[];
    orgaTeams: { id: string; name: string }[];
    themes: { id: string; name: string }[];
}>();

const orgaTeamSelection = ref<string | null>(
    (props.event as Event & { orga_team_id: string | null }).orga_team_id ??
        null,
);

const themeSelection = ref<string | null>(
    (props.event as Event & { theme_id: string | null }).theme_id ?? null,
);

// LAN Party Publishing Standard (LPPS) admin fields. These columns are not on
// the base Event type, so they are read through a permissive record.
const lppsEvent = props.event as unknown as Record<
    string,
    string | number | boolean | null
>;

const attendanceOptions = [
    { value: '1', label: 'In person' },
    { value: '2', label: 'Online' },
    { value: '4', label: 'Hybrid' },
];

const statusOptions = [
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'postponed', label: 'Postponed' },
    { value: 'rescheduled', label: 'Rescheduled' },
    { value: 'moved_online', label: 'Moved online' },
];

const policyGroups = [
    {
        field: 'sleeping',
        label: 'Sleeping arrangements',
        flags: [
            { value: 1, label: 'Not overnight' },
            { value: 2, label: 'Private rooms' },
            { value: 4, label: 'Shared rooms' },
            { value: 8, label: 'Camping' },
        ],
    },
    {
        field: 'alcohol_policy',
        label: 'Alcohol policy',
        flags: [
            { value: 1, label: 'Prohibited' },
            { value: 2, label: 'BYOB permitted' },
            { value: 4, label: 'Sold on site' },
            { value: 8, label: 'Designated area only' },
        ],
    },
    {
        field: 'smoking_policy',
        label: 'Smoking policy',
        flags: [
            { value: 1, label: 'Prohibited' },
            { value: 2, label: 'Designated outdoor area' },
            { value: 4, label: 'Designated indoor area' },
            { value: 8, label: 'Vaping allowed' },
        ],
    },
    {
        field: 'age_policy',
        label: 'Age policy',
        flags: [
            { value: 1, label: 'Guardian required for minors' },
            { value: 2, label: 'Minimum age 12' },
            { value: 4, label: 'Minimum age 16' },
            { value: 8, label: 'Minimum age 18' },
        ],
    },
    {
        field: 'food_policy',
        label: 'Food policy',
        flags: [
            { value: 1, label: 'No outside food' },
            { value: 2, label: 'Bring your own permitted' },
            { value: 4, label: 'Food sold on site' },
            { value: 8, label: 'Free food provided' },
        ],
    },
] as const;

const lpps = reactive<Record<string, number>>({
    sleeping: Number(lppsEvent.sleeping ?? 0),
    alcohol_policy: Number(lppsEvent.alcohol_policy ?? 0),
    smoking_policy: Number(lppsEvent.smoking_policy ?? 0),
    age_policy: Number(lppsEvent.age_policy ?? 0),
    food_policy: Number(lppsEvent.food_policy ?? 0),
});

const hasShowers = ref(Boolean(lppsEvent.has_showers));
const attendanceDefault = String(lppsEvent.attendance_mode ?? 1);
const statusDefault = String(lppsEvent.syndication_status ?? 'scheduled');

function mbpsDefault(field: string): string {
    const value = lppsEvent[field];

    return value === null || value === undefined ? '' : String(value);
}

function hasFlag(field: string, value: number): boolean {
    return (lpps[field] & value) === value;
}

function toggleFlag(field: string, value: number, event: globalThis.Event) {
    const checked = (event.target as HTMLInputElement).checked;
    lpps[field] = checked ? lpps[field] | value : lpps[field] & ~value;
}

function saveOrgaTeam() {
    router.patch(
        `/events/${props.event.id}/orga-team`,
        { orga_team_id: orgaTeamSelection.value },
        { preserveScroll: true },
    );
}

function saveTheme() {
    router.patch(
        `/events/${props.event.id}/theme`,
        { theme_id: themeSelection.value },
        { preserveScroll: true },
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('common.administration'), href: eventsRoute().url },
    { title: t('navigation.events'), href: eventsRoute().url },
    { title: props.event.name, href: EventController.edit(props.event.id).url },
];

function formatDateTimeLocal(dateString: string): string {
    const date = new Date(dateString);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

const showDeleteDialog = ref(false);
const publishErrors = ref<Record<string, string>>({});

// Existing images still retained (not yet marked for removal).
const retainedImages = ref<{ path: string; url: string }[]>(
    (props.event.banner_images ?? []).map((path, i) => ({
        path,
        url: props.event.banner_image_urls[i] ?? '',
    })),
);
const imagesToRemove = ref<string[]>([]);

function markImageForRemoval(index: number) {
    const img = retainedImages.value[index];
    imagesToRemove.value.push(img.path);
    retainedImages.value.splice(index, 1);
}

// Newly picked images (not yet uploaded).
const newBannerSlots = ref<{ id: string; preview: string }[]>([]);
let nextSlotId = 0;

function addBannerSlot() {
    newBannerSlots.value.push({ id: nextSlotId++, preview: '' });
}

function onNewBannerSelected(index: number, event: globalThis.Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        newBannerSlots.value[index].preview = URL.createObjectURL(file);
    }
}

function removeNewBannerSlot(index: number) {
    const preview = newBannerSlots.value[index].preview;

    if (preview) {
        URL.revokeObjectURL(preview);
    }

    newBannerSlots.value.splice(index, 1);
}

function executeDelete() {
    router.delete(EventController.destroy(props.event.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}

function publishEvent() {
    publishErrors.value = {};
    router.patch(
        EventController.publish(props.event.id).url,
        {},
        {
            preserveScroll: true,
            onError: (errors) => {
                publishErrors.value = errors;
            },
        },
    );
}

function unpublishEvent() {
    router.patch(
        EventController.unpublish(props.event.id).url,
        {},
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head :title="$t('events.editTitle', { name: event.name })" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="eventsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ $t('events.backToList') }}
                </Link>
            </div>

            <!-- Status & Publishing -->
            <div class="flex items-center gap-4">
                <Badge
                    :variant="
                        event.status === 'published' ? 'default' : 'secondary'
                    "
                >
                    {{
                        event.status === 'published'
                            ? $t('common.published')
                            : $t('common.draft')
                    }}
                </Badge>
                <Button
                    v-if="event.status === 'draft'"
                    size="sm"
                    @click="publishEvent"
                >
                    {{ $t('events.publish') }}
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    @click="unpublishEvent"
                >
                    {{ $t('events.unpublish') }}
                </Button>
            </div>
            <div v-if="Object.keys(publishErrors).length > 0" class="space-y-1">
                <p
                    v-for="(message, field) in publishErrors"
                    :key="field"
                    class="text-sm text-destructive"
                >
                    {{ message }}
                </p>
            </div>

            <Form
                v-bind="EventController.update.form(event.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Event Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        :title="$t('events.form.eventInfoHeading')"
                        :description="
                            $t('events.form.eventInfoEditDescription')
                        "
                    />

                    <div class="grid gap-2">
                        <Label for="name">{{ $t('common.name') }}</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="event.name"
                            required
                            :placeholder="$t('events.form.namePlaceholder')"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">{{
                            $t('common.description')
                        }}</Label>
                        <Textarea
                            id="description"
                            name="description"
                            :default-value="event.description ?? ''"
                            rows="4"
                            :placeholder="
                                $t('events.form.descriptionPlaceholder')
                            "
                        />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <!-- Schedule -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        :title="$t('events.form.scheduleHeading')"
                        :description="$t('events.form.scheduleEditDescription')"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">{{
                                $t('events.form.startDate')
                            }}</Label>
                            <Input
                                id="start_date"
                                type="datetime-local"
                                name="start_date"
                                :default-value="
                                    formatDateTimeLocal(event.start_date)
                                "
                                required
                            />
                            <InputError :message="errors.start_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_date">{{
                                $t('events.form.endDate')
                            }}</Label>
                            <Input
                                id="end_date"
                                type="datetime-local"
                                name="end_date"
                                :default-value="
                                    formatDateTimeLocal(event.end_date)
                                "
                                required
                            />
                            <InputError :message="errors.end_date" />
                        </div>
                    </div>
                </div>

                <!-- Venue & Capacity -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        :title="$t('events.form.venueCapacityHeading')"
                        :description="
                            $t('events.form.venueCapacityEditDescription')
                        "
                    />

                    <div class="grid gap-2">
                        <Label for="venue_id">{{
                            $t('events.form.venue')
                        }}</Label>
                        <Select
                            name="venue_id"
                            :default-value="
                                event.venue_id
                                    ? String(event.venue_id)
                                    : undefined
                            "
                        >
                            <SelectTrigger>
                                <SelectValue
                                    :placeholder="
                                        $t('events.form.venuePlaceholder')
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="venue in venues"
                                    :key="venue.id"
                                    :value="String(venue.id)"
                                >
                                    {{ venue.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.venue_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="seat_capacity">{{
                            $t('events.form.seatCapacity')
                        }}</Label>
                        <Input
                            id="seat_capacity"
                            type="number"
                            name="seat_capacity"
                            :default-value="
                                event.seat_capacity
                                    ? String(event.seat_capacity)
                                    : ''
                            "
                            min="0"
                            :placeholder="
                                $t('events.form.seatCapacityPlaceholder')
                            "
                        />
                        <InputError :message="errors.seat_capacity" />
                    </div>
                </div>

                <!-- Publishing (LAN Party Publishing Standard) -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Publishing (LAN Party Publishing Standard)"
                        description="Optional details syndicated through the public /.well-known/lan-party.json feed."
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="attendance_mode">Attendance mode</Label>
                            <Select
                                name="attendance_mode"
                                :default-value="attendanceDefault"
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in attendanceOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.attendance_mode" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="syndication_status">Event status</Label>
                            <Select
                                name="syndication_status"
                                :default-value="statusDefault"
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in statusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.syndication_status" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            class="size-4 rounded border-input"
                            v-model="hasShowers"
                        />
                        Showers available on site
                    </label>
                    <input
                        type="hidden"
                        name="has_showers"
                        :value="hasShowers ? '1' : '0'"
                    />

                    <div
                        v-for="group in policyGroups"
                        :key="group.field"
                        class="grid gap-2"
                    >
                        <Label>{{ group.label }}</Label>
                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                            <label
                                v-for="flag in group.flags"
                                :key="flag.value"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                    :checked="hasFlag(group.field, flag.value)"
                                    @change="
                                        toggleFlag(
                                            group.field,
                                            flag.value,
                                            $event,
                                        )
                                    "
                                />
                                {{ flag.label }}
                            </label>
                        </div>
                        <input
                            type="hidden"
                            :name="group.field"
                            :value="lpps[group.field]"
                        />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="network_connection_mbps"
                                >LAN (Mbps)</Label
                            >
                            <Input
                                id="network_connection_mbps"
                                type="number"
                                name="network_connection_mbps"
                                min="0"
                                :default-value="
                                    mbpsDefault('network_connection_mbps')
                                "
                                placeholder="e.g. 10000"
                            />
                            <InputError
                                :message="errors.network_connection_mbps"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="internet_connection_mbps"
                                >Internet (Mbps)</Label
                            >
                            <Input
                                id="internet_connection_mbps"
                                type="number"
                                name="internet_connection_mbps"
                                min="0"
                                :default-value="
                                    mbpsDefault('internet_connection_mbps')
                                "
                                placeholder="e.g. 1000"
                            />
                            <InputError
                                :message="errors.internet_connection_mbps"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="wifi_connection_mbps"
                                >Wi-Fi (Mbps)</Label
                            >
                            <Input
                                id="wifi_connection_mbps"
                                type="number"
                                name="wifi_connection_mbps"
                                min="0"
                                :default-value="
                                    mbpsDefault('wifi_connection_mbps')
                                "
                                placeholder="e.g. 300"
                            />
                            <InputError
                                :message="errors.wifi_connection_mbps"
                            />
                        </div>
                    </div>
                </div>

                <!-- Media -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        :title="$t('events.form.mediaHeading')"
                        :description="$t('events.form.mediaEditDescription')"
                    />

                    <div class="grid gap-3">
                        <Label>{{ $t('events.form.bannerImages') }}</Label>

                        <!-- Retained existing images -->
                        <div
                            v-for="(img, index) in retainedImages"
                            :key="img.path"
                            class="flex items-start gap-3"
                        >
                            <img
                                :src="img.url"
                                alt="Banner image"
                                class="h-20 w-36 shrink-0 rounded-md border object-cover"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="mt-1"
                                @click="markImageForRemoval(index)"
                            >
                                <X class="size-4" />
                                {{ $t('common.remove') }}
                            </Button>
                        </div>

                        <!-- Hidden inputs for images to remove -->
                        <template v-for="path in imagesToRemove" :key="path">
                            <input
                                type="hidden"
                                name="banner_images_to_remove[]"
                                :value="path"
                            />
                        </template>

                        <!-- New image upload slots -->
                        <div
                            v-for="(slot, index) in newBannerSlots"
                            :key="slot.id"
                            class="flex items-start gap-3"
                        >
                            <div class="flex-1">
                                <label
                                    :for="`new_banner_${slot.id}`"
                                    class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground ring-offset-background hover:bg-accent hover:text-accent-foreground"
                                >
                                    <ImagePlus class="size-4" />
                                    {{
                                        slot.preview
                                            ? $t('events.form.replaceImage')
                                            : $t('events.form.chooseImage')
                                    }}
                                </label>
                                <input
                                    :id="`new_banner_${slot.id}`"
                                    type="file"
                                    name="banner_images[]"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="sr-only"
                                    @change="onNewBannerSelected(index, $event)"
                                />
                                <img
                                    v-if="slot.preview"
                                    :src="slot.preview"
                                    alt="New banner preview"
                                    class="mt-2 max-h-36 rounded-md border object-cover"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="mt-1 shrink-0"
                                @click="removeNewBannerSlot(index)"
                            >
                                <X class="size-4" />
                                {{ $t('common.remove') }}
                            </Button>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="w-fit"
                            @click="addBannerSlot"
                        >
                            <ImagePlus class="size-4" />
                            {{ $t('common.addImage') }}
                        </Button>

                        <p class="text-xs text-muted-foreground">
                            {{ $t('events.form.acceptedFormats') }}
                        </p>
                        <InputError
                            :message="
                                (errors as Record<string, string>)[
                                    'banner_images'
                                ]
                            "
                        />
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

            <!-- Orga-Team assignment -->
            <div class="space-y-3 border-t pt-6">
                <div>
                    <h3 class="text-sm font-medium">Orga-Team</h3>
                    <p class="text-sm text-muted-foreground">
                        Pick the staff team shown publicly for this event. Leave
                        unset to hide the team section.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Select
                        :model-value="
                            orgaTeamSelection === null
                                ? '__none__'
                                : String(orgaTeamSelection)
                        "
                        @update:model-value="
                            (v) =>
                                (orgaTeamSelection =
                                    v === '__none__' || !v ? null : String(v))
                        "
                    >
                        <SelectTrigger class="max-w-sm">
                            <SelectValue placeholder="No team assigned" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__none__">
                                No team assigned
                            </SelectItem>
                            <SelectItem
                                v-for="team in orgaTeams"
                                :key="team.id"
                                :value="String(team.id)"
                            >
                                {{ team.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="button" @click="saveOrgaTeam">
                        Save assignment
                    </Button>
                </div>
            </div>

            <!-- Theme assignment -->
            <div class="space-y-3 border-t pt-6">
                <div>
                    <h3 class="text-sm font-medium">Event theme</h3>
                    <p class="text-sm text-muted-foreground">
                        Pick a theme from the
                        <Link :href="$page.props.route ?? ''" class="underline">
                            theme library
                        </Link>
                        to govern the appearance of every page under this
                        event's URL. Leave unset to use the platform default.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Select
                        :model-value="
                            themeSelection === null
                                ? '__none__'
                                : String(themeSelection)
                        "
                        @update:model-value="
                            (v) =>
                                (themeSelection =
                                    v === '__none__' || !v ? null : String(v))
                        "
                    >
                        <SelectTrigger class="max-w-sm">
                            <SelectValue placeholder="Default appearance" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__none__">
                                Default appearance
                            </SelectItem>
                            <SelectItem
                                v-for="theme in themes"
                                :key="theme.id"
                                :value="String(theme.id)"
                            >
                                {{ theme.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="button" @click="saveTheme">
                        Save assignment
                    </Button>
                </div>
            </div>

            <!-- Delete section -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">
                            {{ $t('events.deleteHeading') }}
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            {{ $t('events.deleteHint') }}
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
                        $t('events.deleteConfirmTitle', { name: event.name })
                    }}</DialogTitle>
                    <DialogDescription>
                        {{ $t('events.deleteConfirmDescription') }}
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
