<script setup lang="ts">
import VenueController from '@/actions/App/Domain/Venue/Http/Controllers/VenueController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as venuesRoute } from '@/routes/venues'
import type { BreadcrumbItem } from '@/types'
import { Form, Head, Link } from '@inertiajs/vue3'
import { Plus, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: venuesRoute().url },
    { title: 'Venues', href: venuesRoute().url },
    { title: 'Create', href: VenueController.create().url },
]

const images = ref<{ path: string; alt_text: string }[]>([])

function addImage() {
    images.value.push({ path: '', alt_text: '' })
}

function removeImage(index: number) {
    images.value.splice(index, 1)
}
</script>

<template>
    <Head title="Create Venue" />

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
                        description="Add image paths for this venue"
                    />

                    <div
                        v-for="(image, index) in images"
                        :key="index"
                        class="flex items-start gap-2"
                    >
                        <div class="grid flex-1 gap-2">
                            <Input
                                v-model="image.path"
                                :name="`images[${index}][path]`"
                                placeholder="Image path (e.g. images/venues/photo.jpg)"
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
                    <InputError :message="errors.images" />
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Creating…' : 'Create Venue' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
