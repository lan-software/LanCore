<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Building2,
    CheckCircle2,
    ImagePlus,
    Loader2,
    Trash2,
} from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as orgSettingsRoute } from '@/routes/organization-settings';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    settings: Record<string, string | null>;
    logoUrl: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: orgSettingsRoute().url },
    { title: 'Organization', href: orgSettingsRoute().url },
];

const form = reactive({
    name: props.settings.name ?? '',
    address_line1: props.settings.address_line1 ?? '',
    address_line2: props.settings.address_line2 ?? '',
    email: props.settings.email ?? '',
    phone: props.settings.phone ?? '',
    website: props.settings.website ?? '',
    tax_id: props.settings.tax_id ?? '',
    registration_id: props.settings.registration_id ?? '',
    legal_notice: props.settings.legal_notice ?? '',
});

const saving = ref(false);
const saved = ref(false);

function save() {
    saving.value = true;
    saved.value = false;
    router.patch(orgSettingsRoute().url, form, {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            setTimeout(() => (saved.value = false), 2000);
        },
        onFinish: () => (saving.value = false),
    });
}

const logoInput = ref<HTMLInputElement>();
const uploadingLogo = ref(false);

function triggerLogoUpload() {
    logoInput.value?.click();
}

function uploadLogo(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    uploadingLogo.value = true;
    router.post(
        '/organization-settings/logo',
        { logo: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => (uploadingLogo.value = false),
        },
    );
}

function removeLogo() {
    if (!window.confirm('Remove the organization logo?')) {
        return;
    }

    router.delete('/organization-settings/logo', { preserveScroll: true });
}
</script>

<template>
    <Head title="Organization Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-3xl flex-1 flex-col gap-6 p-4">
            <Heading
                title="Organization Settings"
                description="Configure your organization's legal and contact information. This data appears on invoices, receipts, and legal documents across all features."
            />

            <!-- Logo -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <ImagePlus class="size-4 text-muted-foreground" />
                    Organization Logo
                </div>
                <div class="flex items-center gap-4">
                    <div
                        class="flex size-24 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-border bg-muted/30"
                    >
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            alt="Organization logo"
                            class="size-full object-contain p-2"
                        />
                        <ImagePlus
                            v-else
                            class="size-8 text-muted-foreground/40"
                        />
                    </div>
                    <div class="space-y-2">
                        <input
                            ref="logoInput"
                            type="file"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="hidden"
                            @change="uploadLogo"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="uploadingLogo"
                            @click="triggerLogoUpload"
                        >
                            {{
                                uploadingLogo
                                    ? 'Uploading...'
                                    : logoUrl
                                      ? 'Change Logo'
                                      : 'Upload Logo'
                            }}
                        </Button>
                        <Button
                            v-if="logoUrl"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="text-destructive"
                            @click="removeLogo"
                        >
                            <Trash2 class="mr-1 size-3" /> Remove
                        </Button>
                        <p class="text-xs text-muted-foreground">
                            PNG, JPG, SVG or WebP. Max 2MB. Shown on invoices
                            and receipts.
                        </p>
                    </div>
                </div>
            </div>

            <form class="space-y-8" @submit.prevent="save">
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <Building2 class="size-4 text-muted-foreground" />
                        Organization Details
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="name">Organization Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. LAN Party e.V."
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="address_line1">Address Line 1</Label>
                            <Input
                                id="address_line1"
                                v-model="form.address_line1"
                                placeholder="Street and number"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="address_line2">Address Line 2</Label>
                            <Input
                                id="address_line2"
                                v-model="form.address_line2"
                                placeholder="ZIP, City, Country"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="email">Contact Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="info@example.com"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="phone">Phone</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="+49 123 456789"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="website">Website</Label>
                            <Input
                                id="website"
                                v-model="form.website"
                                type="url"
                                placeholder="https://example.com"
                            />
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="text-sm font-semibold">Legal Information</div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="tax_id">Tax ID / VAT Number</Label>
                            <Input
                                id="tax_id"
                                v-model="form.tax_id"
                                placeholder="e.g. DE123456789"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="registration_id">Registration ID</Label>
                            <Input
                                id="registration_id"
                                v-model="form.registration_id"
                                placeholder="e.g. VR 12345, Amtsgericht Berlin"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="legal_notice"
                                >Legal Notice / Disclaimer</Label
                            >
                            <Textarea
                                id="legal_notice"
                                v-model="form.legal_notice"
                                rows="3"
                                placeholder="Additional legal text printed on invoices and receipts..."
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="saving">
                        <Loader2
                            v-if="saving"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </Button>
                    <span
                        v-if="saved"
                        class="flex items-center gap-1 text-sm text-green-600"
                    >
                        <CheckCircle2 class="size-4" /> Saved
                    </span>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
