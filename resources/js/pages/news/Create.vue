<script setup lang="ts">
import { store } from '@/actions/App/Domain/News/Http/Controllers/NewsArticleController'
import { create as newsCreate } from '@/actions/App/Domain/News/Http/Controllers/NewsArticleController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as newsRoute } from '@/routes/news'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { X } from 'lucide-vue-next'
import { ref } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: newsRoute().url },
    { title: 'News', href: newsRoute().url },
    { title: 'Create', href: newsCreate().url },
]

const form = useForm({
    title: '',
    summary: '',
    content: '',
    tags: [] as string[],
    image: null as File | null,
    visibility: 'draft',
    is_archived: false,
    comments_enabled: true,
    comments_require_approval: false,
    notify_users: false,
    meta_title: '',
    meta_description: '',
    og_title: '',
    og_description: '',
    og_image: null as File | null,
    published_at: '',
})

const tagInput = ref('')
const imagePreview = ref<string | null>(null)
const ogImagePreview = ref<string | null>(null)

function addTag() {
    const tag = tagInput.value.trim().toLowerCase()
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag)
    }
    tagInput.value = ''
}

function removeTag(index: number) {
    form.tags.splice(index, 1)
}

function handleTagKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault()
        addTag()
    }
}

function handleImageChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) {
        form.image = file
        imagePreview.value = URL.createObjectURL(file)
    }
}

function handleOgImageChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) {
        form.og_image = file
        ogImagePreview.value = URL.createObjectURL(file)
    }
}

function submit() {
    form.post(store().url)
}
</script>

<template>
    <Head title="Create Article" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-3xl">
            <div>
                <Link :href="newsRoute().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to News
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <Heading variant="small" title="Article Information" description="Provide the basic details for the news article" />

                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input id="title" v-model="form.title" required placeholder="Article title" />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="summary">Summary</Label>
                        <Textarea id="summary" v-model="form.summary" rows="3" placeholder="Brief summary shown on the welcome page…" />
                        <p class="text-xs text-muted-foreground">Max 500 characters. Displayed as a preview on the homepage.</p>
                        <InputError :message="form.errors.summary" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Content</Label>
                        <RichTextEditor v-model="form.content" placeholder="Write the article content…" />
                        <InputError :message="form.errors.content" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tags">Tags</Label>
                        <div class="flex flex-wrap gap-1.5 mb-1.5">
                            <span v-for="(tag, index) in form.tags" :key="tag" class="inline-flex items-center gap-1 rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium">
                                {{ tag }}
                                <button type="button" @click="removeTag(index)" class="text-muted-foreground hover:text-foreground">
                                    <X class="size-3" />
                                </button>
                            </span>
                        </div>
                        <Input id="tags" v-model="tagInput" placeholder="Type a tag and press Enter…" @keydown="handleTagKeydown" @blur="addTag" />
                        <InputError :message="form.errors.tags" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="image">Banner Image</Label>
                        <Input id="image" type="file" accept="image/*" @change="handleImageChange" />
                        <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="mt-2 max-h-48 rounded-md object-cover" />
                        <InputError :message="form.errors.image" />
                    </div>
                </div>

                <!-- Visibility & Settings -->
                <div class="space-y-4">
                    <Heading variant="small" title="Publishing" description="Control visibility and publishing settings" />

                    <div class="grid gap-2">
                        <Label for="visibility">Visibility</Label>
                        <Select v-model="form.visibility">
                            <SelectTrigger>
                                <SelectValue placeholder="Select visibility" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="internal">Internal</SelectItem>
                                <SelectItem value="public">Public</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.visibility" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="published_at">Publish Date</Label>
                        <Input id="published_at" v-model="form.published_at" type="datetime-local" />
                        <p class="text-xs text-muted-foreground">Leave empty to publish immediately when visibility is set to public.</p>
                        <InputError :message="form.errors.published_at" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_archived" v-model:checked="form.is_archived" />
                        <Label for="is_archived" class="text-sm font-normal">Archived</Label>
                    </div>
                </div>

                <!-- Comment Settings -->
                <div class="space-y-4">
                    <Heading variant="small" title="Comments" description="Configure comment settings for this article" />

                    <div class="flex items-center gap-2">
                        <Checkbox id="comments_enabled" v-model:checked="form.comments_enabled" />
                        <Label for="comments_enabled" class="text-sm font-normal">Allow comments</Label>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="comments_require_approval" v-model:checked="form.comments_require_approval" />
                        <Label for="comments_require_approval" class="text-sm font-normal">Comments require admin approval</Label>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="space-y-4">
                    <Heading variant="small" title="Notifications" description="Configure user notification settings" />

                    <div class="flex items-center gap-2">
                        <Checkbox id="notify_users" v-model:checked="form.notify_users" />
                        <Label for="notify_users" class="text-sm font-normal">Notify users when published</Label>
                    </div>
                </div>

                <!-- SEO Settings -->
                <div class="space-y-4">
                    <Heading variant="small" title="SEO & Social" description="Optimize search engine and social media presence" />

                    <div class="grid gap-2">
                        <Label for="meta_title">Meta Title</Label>
                        <Input id="meta_title" v-model="form.meta_title" placeholder="SEO title (max 70 chars)" maxlength="70" />
                        <p class="text-xs text-muted-foreground">{{ (form.meta_title?.length ?? 0) }}/70 characters</p>
                        <InputError :message="form.errors.meta_title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="meta_description">Meta Description</Label>
                        <Textarea id="meta_description" v-model="form.meta_description" rows="2" placeholder="SEO description (max 160 chars)" maxlength="160" />
                        <p class="text-xs text-muted-foreground">{{ (form.meta_description?.length ?? 0) }}/160 characters</p>
                        <InputError :message="form.errors.meta_description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="og_title">OpenGraph Title</Label>
                        <Input id="og_title" v-model="form.og_title" placeholder="Social media share title" maxlength="70" />
                        <InputError :message="form.errors.og_title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="og_description">OpenGraph Description</Label>
                        <Textarea id="og_description" v-model="form.og_description" rows="2" placeholder="Social media share description" maxlength="200" />
                        <InputError :message="form.errors.og_description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="og_image">OpenGraph Image</Label>
                        <Input id="og_image" type="file" accept="image/*" @change="handleOgImageChange" />
                        <p class="text-xs text-muted-foreground">Recommended size: 1200×630 pixels</p>
                        <img v-if="ogImagePreview" :src="ogImagePreview" alt="OG Preview" class="mt-2 max-h-32 rounded-md object-cover" />
                        <InputError :message="form.errors.og_image" />
                    </div>
                </div>

                <Button type="submit" :disabled="form.processing">Create Article</Button>
            </form>
        </div>
    </AppLayout>
</template>
