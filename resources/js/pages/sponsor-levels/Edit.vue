<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3'
import { Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { edit as sponsorLevelEdit, update, destroy } from '@/actions/App/Domain/Sponsoring/Http/Controllers/SponsorLevelController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as sponsorLevelsRoute } from '@/routes/sponsor-levels'
import type { BreadcrumbItem } from '@/types'
import type { SponsorLevel } from '@/types/domain'

const props = defineProps<{
    sponsorLevel: SponsorLevel
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: sponsorLevelsRoute().url },
    { title: 'Sponsor Levels', href: sponsorLevelsRoute().url },
    { title: props.sponsorLevel.name, href: sponsorLevelEdit(props.sponsorLevel.id).url },
]

const showDeleteDialog = ref(false)

function executeDelete() {
    router.delete(destroy(props.sponsorLevel.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false
        },
    })
}
</script>

<template>
    <Head :title="`Edit ${sponsorLevel.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <div>
                <Link
                    :href="sponsorLevelsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Sponsor Levels
                </Link>
            </div>

            <Form
                v-bind="update.form(sponsorLevel.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Sponsor Level"
                        description="Update this sponsor tier"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="sponsorLevel.name"
                            required
                            placeholder="e.g. Gold, Silver, Bronze"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="color">Color</Label>
                        <div class="flex items-center gap-3">
                            <Input
                                id="color"
                                name="color"
                                type="color"
                                class="h-10 w-16 p-1"
                                :value="sponsorLevel.color"
                            />
                            <span
                                class="inline-block size-6 rounded-full border"
                                :style="{ backgroundColor: sponsorLevel.color }"
                            />
                            <span class="text-sm text-muted-foreground">{{ sponsorLevel.color }}</span>
                        </div>
                        <InputError :message="errors.color" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="sort_order">Sort Order</Label>
                        <Input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            :default-value="String(sponsorLevel.sort_order)"
                            min="0"
                        />
                        <InputError :message="errors.sort_order" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                    <p v-if="recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                </div>
            </Form>

            <!-- Delete section -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">Delete Level</h3>
                        <p class="text-sm text-muted-foreground">Remove this sponsor level. Sponsors using it will have their level unset.</p>
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

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ sponsorLevel.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. Sponsors using this level will have their level unset.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="executeDelete">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
