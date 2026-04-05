<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ImagePlus, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import VenueController from '@/actions/App/Domain/Venue/Http/Controllers/VenueController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as venuesRoute } from '@/routes/venues';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: venuesRoute().url },
    { title: 'Venues', href: venuesRoute().url },
    { title: 'Create', href: VenueController.create().url },
];

const images = ref<
    { file: File | null; alt_text: string; preview: string | null }[]
>([]);

function addImage() {
    images.value.push({ file: null, alt_text: '', preview: null });
}

function removeImage(index: number) {
    images.value.splice(index, 1);
}

function onFileSelected(index: number, event: globalThis.Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        images.value[index].file = file;
        images.value[index].preview = URL.createObjectURL(file);
    }
}
</script>

<template>
    <Head title="Create Venue" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="venuesRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Venues
                </Link>
            </div>

            <Form
                v-bind="VenueController.store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Venue Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Venue Information"
                        description="Provide the basic details for this venue"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="Venue name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Describe the venue…"
                        />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <!-- Address -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Address"
                        description="The physical location of this venue"
                    />

                    <div class="grid gap-2">
                        <Label for="street">Street</Label>
                        <Input
                            id="street"
                            name="street"
                            required
                            placeholder="Street address"
                        />
                        <InputError :message="errors.street" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="city">City</Label>
                            <Input
                                id="city"
                                name="city"
                                required
                                placeholder="City"
                            />
                            <InputError :message="errors.city" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="zip_code">ZIP Code</Label>
                            <Input
                                id="zip_code"
                                name="zip_code"
                                required
                                placeholder="ZIP code"
                            />
                            <InputError :message="errors.zip_code" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="state">State / Region</Label>
                            <Input
                                id="state"
                                name="state"
                                placeholder="State (optional)"
                            />
                            <InputError :message="errors.state" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="country">Country</Label>
                            <Input
                                id="country"
                                name="country"
                                required
                                placeholder="Country"
                            />
                            <InputError :message="errors.country" />
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Images"
                        description="Upload images for this venue"
                    />

                    <div
                        v-for="(image, index) in images"
                        :key="index"
                        class="flex items-start gap-2 rounded-md border p-3"
                    >
                        <div class="grid flex-1 gap-2">
                            <div class="flex items-center gap-2">
                                <label
                                    :for="`image_file_${index}`"
                                    class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground ring-offset-background hover:bg-accent hover:text-accent-foreground"
                                >
                                    <ImagePlus class="size-4" />
                                    {{
                                        image.file
                                            ? image.file.name
                                            : 'Choose Image'
                                    }}
                                </label>
                                <input
                                    :id="`image_file_${index}`"
                                    type="file"
                                    :name="`images[${index}][file]`"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="sr-only"
                                    @change="onFileSelected(index, $event)"
                                />
                            </div>
                            <img
                                v-if="image.preview"
                                :src="image.preview"
                                alt="Preview"
                                class="max-h-32 rounded-md border object-cover"
                            />
                            <Input
                                v-model="image.alt_text"
                                :name="`images[${index}][alt_text]`"
                                placeholder="Alt text (optional)"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="mt-1"
                            @click="removeImage(index)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addImage"
                    >
                        <Plus class="size-4" />
                        Add Image
                    </Button>
                    <p class="text-xs text-muted-foreground">
                        Accepted formats: JPEG, PNG, GIF, WebP. Max 5 MB each.
                    </p>
                    <InputError :message="errors.images" />
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating…' : 'Create Venue' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
