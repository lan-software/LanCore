<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3'
import { ImagePlus, Plus, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import VenueController from '@/actions/App/Domain/Venue/Http/Controllers/VenueController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as venuesRoute } from '@/routes/venues'
import type { BreadcrumbItem } from '@/types'
import type { Venue } from '@/types/domain'

const props = defineProps<{
    venue: Venue
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: venuesRoute().url },
    { title: 'Venues', href: venuesRoute().url },
    { title: props.venue.name, href: VenueController.edit(props.venue.id).url },
]

const existingImages = ref(
    (props.venue.images ?? []).map((img) => ({
        id: img.id,
        url: img.url,
        alt_text: img.alt_text ?? '',
    })),
)

const newImages = ref<{ file: File | null; alt_text: string; preview: string | null }[]>([])

function removeExistingImage(index: number) {
    existingImages.value.splice(index, 1)
}

function addNewImage() {
    newImages.value.push({ file: null, alt_text: '', preview: null })
}

function removeNewImage(index: number) {
    newImages.value.splice(index, 1)
}

function onNewFileSelected(index: number, event: globalThis.Event) {
    const file = (event.target as HTMLInputElement).files?.[0]

    if (file) {
        newImages.value[index].file = file
        newImages.value[index].preview = URL.createObjectURL(file)
    }
}

const showDeleteDialog = ref(false)

function executeDelete() {
    router.delete(VenueController.destroy(props.venue.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false
        },
    })
}
</script>

<template>
    <Head :title="`Edit ${venue.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
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
                v-bind="VenueController.update.form(venue.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Venue Info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Venue Information"
                        description="Update the basic details for this venue"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="venue.name"
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
                            :default-value="venue.description ?? ''"
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
                            :default-value="venue.address?.street"
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
                                :default-value="venue.address?.city"
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
                                :default-value="venue.address?.zip_code"
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
                                :default-value="venue.address?.state ?? ''"
                                placeholder="State (optional)"
                            />
                            <InputError :message="errors.state" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="country">Country</Label>
                            <Input
                                id="country"
                                name="country"
                                :default-value="venue.address?.country"
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
                        description="Manage images for this venue"
                    />

                    <!-- Existing images -->
                    <div
                        v-for="(image, index) in existingImages"
                        :key="`existing-${image.id}`"
                        class="flex items-start gap-2 rounded-md border p-3"
                    >
                        <input
                            type="hidden"
                            :name="`existing_images[${index}][id]`"
                            :value="image.id"
                        />
                        <div class="grid flex-1 gap-2">
                            <img
                                :src="image.url"
                                :alt="image.alt_text"
                                class="max-h-32 rounded-md border object-cover"
                            />
                            <Input
                                v-model="image.alt_text"
                                :name="`existing_images[${index}][alt_text]`"
                                placeholder="Alt text (optional)"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="mt-1"
                            @click="removeExistingImage(index)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>

                    <!-- New image uploads -->
                    <div
                        v-for="(image, index) in newImages"
                        :key="`new-${index}`"
                        class="flex items-start gap-2 rounded-md border border-dashed p-3"
                    >
                        <div class="grid flex-1 gap-2">
                            <div class="flex items-center gap-2">
                                <label
                                    :for="`new_image_file_${index}`"
                                    class="flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground ring-offset-background hover:bg-accent hover:text-accent-foreground"
                                >
                                    <ImagePlus class="size-4" />
                                    {{ image.file ? image.file.name : 'Choose Image' }}
                                </label>
                                <input
                                    :id="`new_image_file_${index}`"
                                    type="file"
                                    :name="`new_images[${index}][file]`"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="sr-only"
                                    @change="onNewFileSelected(index, $event)"
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
                                :name="`new_images[${index}][alt_text]`"
                                placeholder="Alt text (optional)"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="mt-1"
                            @click="removeNewImage(index)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addNewImage"
                    >
                        <Plus class="size-4" />
                        Add Image
                    </Button>
                    <p class="text-xs text-muted-foreground">Accepted formats: JPEG, PNG, GIF, WebP. Max 5 MB each.</p>
                    <InputError :message="errors['existing_images'] || errors['new_images']" />
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
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
                        <h3 class="text-sm font-medium text-destructive">Delete Venue</h3>
                        <p class="text-sm text-muted-foreground">Permanently delete this venue and its address.</p>
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
                    <DialogTitle>Delete {{ venue.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The venue and its address will be permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showDeleteDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="executeDelete"
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
